<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Office;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Support\Haversine;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Attendance check-in / check-out flow.
 *
 * Per Decision 7 (verdict-based face verification) + Decision 10 (timezone):
 *   - Store event_at in UTC, bucket event_date in Africa/Cairo
 *   - Outside radius → block; inside radius → require selfie + verdict
 *   - verified (>=0.90), possibly_self (0.70-0.90, HR flag), unverified (<0.70, block)
 *   - Selfie path stored; cron purges file + nulls path 24h after event (PDPL)
 *
 * Late detection: event_at compared against employee.shift_start_time in
 * Africa/Cairo with a 5-minute grace window.
 *
 * UI integration (camera + geolocation) is deferred to Walid's browser QA
 * (Checkpoint B). This service is the trustworthy server-side logic; the
 * frontend submits raw lat/long + selfie path + descriptor-match score.
 */
class AttendanceService extends BaseService
{
    // Decision 7 verdict thresholds (face descriptor cosine similarity 0-1)
    public const VERDICT_VERIFIED_MIN = 0.90;

    public const VERDICT_POSSIBLY_SELF_MIN = 0.70;

    // Late grace window in minutes
    public const LATE_GRACE_MINUTES = 5;

    // Default check-in radius if office doesn't override
    public const DEFAULT_RADIUS_METRES = 100;

    public function __construct(private readonly AttendanceRepositoryInterface $repo) {}

    /**
     * Record a check-in event.
     * Throws ValidationException with field errors that the controller maps
     * to FormRequest-style responses.
     *
     * @param array{
     *   latitude: float, longitude: float, accuracy_meters?: float,
     *   verdict_score: float, selfie_path?: ?string
     * } $payload
     */
    public function checkIn(Employee $employee, array $payload): AttendanceRecord
    {
        $this->assertNotAlreadyCheckedIn($employee);

        $office = $this->nearestOfficeWithinRadius(
            (int) $employee->org_id,
            (float) $payload['latitude'],
            (float) $payload['longitude'],
        );

        if (! $office['within_radius']) {
            throw ValidationException::withMessages([
                'location' => sprintf(
                    '%dm from %s — must be within %dm.',
                    (int) round($office['distance']),
                    $office['office_name'] ?? 'any office',
                    $office['radius'],
                ),
            ]);
        }

        $verdict = $this->scoreToVerdict((float) $payload['verdict_score']);

        if ($verdict === 'unverified') {
            throw ValidationException::withMessages([
                'verdict' => 'Face verification failed — HR has been notified. Try again or contact HR if the issue persists.',
            ]);
        }

        $nowUtc = Carbon::now('UTC');
        $cairoDate = $nowUtc->copy()->setTimezone('Africa/Cairo')->toDateString();

        return $this->transaction(function () use ($employee, $payload, $office, $verdict, $nowUtc, $cairoDate) {
            return $this->repo->create([
                'org_id' => $employee->org_id,
                'employee_id' => $employee->id,
                'office_id' => $office['office_id'],
                'type' => 'check_in',
                'event_at' => $nowUtc,
                'event_date' => $cairoDate,
                'is_late' => $this->isLate($employee, $nowUtc),
                'latitude' => $payload['latitude'],
                'longitude' => $payload['longitude'],
                'accuracy_meters' => $payload['accuracy_meters'] ?? null,
                'distance_meters' => $office['distance'],
                'verdict' => $verdict,
                'verdict_score' => $payload['verdict_score'],
                'selfie_path' => $payload['selfie_path'] ?? null,
            ]);
        });
    }

    /**
     * Record a check-out event. Same flow as check-in but inverts the
     * "already checked in" guard.
     */
    public function checkOut(Employee $employee, array $payload): AttendanceRecord
    {
        $last = $this->repo->lastEventForEmployeeToday(
            (int) $employee->org_id,
            (int) $employee->id,
            $this->cairoDateNow(),
        );

        if (! $last || $last->type !== 'check_in') {
            throw ValidationException::withMessages([
                'event' => 'No active check-in to close. Check in first.',
            ]);
        }

        $office = $this->nearestOfficeWithinRadius(
            (int) $employee->org_id,
            (float) $payload['latitude'],
            (float) $payload['longitude'],
        );

        if (! $office['within_radius']) {
            throw ValidationException::withMessages([
                'location' => sprintf(
                    '%dm from %s — must be within %dm.',
                    (int) round($office['distance']),
                    $office['office_name'] ?? 'any office',
                    $office['radius'],
                ),
            ]);
        }

        $verdict = $this->scoreToVerdict((float) $payload['verdict_score']);

        if ($verdict === 'unverified') {
            throw ValidationException::withMessages([
                'verdict' => 'Face verification failed — HR has been notified.',
            ]);
        }

        $nowUtc = Carbon::now('UTC');

        return $this->transaction(function () use ($employee, $payload, $office, $verdict, $nowUtc) {
            return $this->repo->create([
                'org_id' => $employee->org_id,
                'employee_id' => $employee->id,
                'office_id' => $office['office_id'],
                'type' => 'check_out',
                'event_at' => $nowUtc,
                'event_date' => $nowUtc->copy()->setTimezone('Africa/Cairo')->toDateString(),
                'is_late' => false,
                'latitude' => $payload['latitude'],
                'longitude' => $payload['longitude'],
                'accuracy_meters' => $payload['accuracy_meters'] ?? null,
                'distance_meters' => $office['distance'],
                'verdict' => $verdict,
                'verdict_score' => $payload['verdict_score'],
                'selfie_path' => $payload['selfie_path'] ?? null,
            ]);
        });
    }

    /**
     * Convert a face-descriptor cosine similarity score to a verdict bucket.
     * Public so tests + HR review tooling can call it directly.
     */
    public function scoreToVerdict(float $score): string
    {
        if ($score < 0 || $score > 1) {
            throw new \InvalidArgumentException('verdict_score must be between 0 and 1.');
        }

        return match (true) {
            $score >= self::VERDICT_VERIFIED_MIN => 'verified',
            $score >= self::VERDICT_POSSIBLY_SELF_MIN => 'possibly_self',
            default => 'unverified',
        };
    }

    /**
     * Find the nearest office within its check-in radius. Returns metadata
     * about the closest office regardless, so the error message can name
     * which office the user is closest to.
     *
     * @return array{within_radius: bool, office_id: ?int, office_name: ?string, distance: float, radius: int}
     */
    public function nearestOfficeWithinRadius(int $orgId, float $lat, float $lng): array
    {
        $offices = Office::query()->where('org_id', $orgId)->get();

        if ($offices->isEmpty()) {
            return [
                'within_radius' => false,
                'office_id' => null,
                'office_name' => null,
                'distance' => 0.0,
                'radius' => self::DEFAULT_RADIUS_METRES,
            ];
        }

        $closest = null;
        $closestDistance = PHP_FLOAT_MAX;

        foreach ($offices as $office) {
            if ($office->latitude === null || $office->longitude === null) {
                continue;
            }

            $distance = Haversine::distance(
                $lat,
                $lng,
                (float) $office->latitude,
                (float) $office->longitude,
            );

            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closest = $office;
            }
        }

        if (! $closest) {
            return [
                'within_radius' => false,
                'office_id' => null,
                'office_name' => null,
                'distance' => 0.0,
                'radius' => self::DEFAULT_RADIUS_METRES,
            ];
        }

        $radius = (int) ($closest->allowed_check_in_radius_meters ?? self::DEFAULT_RADIUS_METRES);

        return [
            'within_radius' => $closestDistance <= $radius,
            'office_id' => $closest->id,
            'office_name' => $closest->name,
            'distance' => $closestDistance,
            'radius' => $radius,
        ];
    }

    /**
     * Late if event_at (Cairo) is more than LATE_GRACE_MINUTES past
     * the employee's shift_start_time. Respects employee.workweek_days —
     * a weekend check-in is never late.
     */
    public function isLate(Employee $employee, \DateTimeInterface $eventAtUtc): bool
    {
        if (! $employee->shift_start_time) {
            return false;
        }

        $cairo = Carbon::instance($eventAtUtc)->setTimezone('Africa/Cairo');

        // Workweek check — sun/mon/tue/wed/thu by default at YZH
        $workweek = $employee->workweek_days ?? ['sun', 'mon', 'tue', 'wed', 'thu'];
        $dayCode = strtolower(substr($cairo->englishDayOfWeek, 0, 3));
        if (! in_array($dayCode, $workweek, true)) {
            return false;
        }

        $shiftStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $cairo->toDateString().' '.$this->normalizeShiftTime($employee->shift_start_time),
            'Africa/Cairo',
        );

        return $cairo->greaterThan($shiftStart->copy()->addMinutes(self::LATE_GRACE_MINUTES));
    }

    /**
     * Mark an attendance record's selfie as purged (file deletion is the
     * caller's responsibility — usually a scheduled command).
     */
    public function markSelfiePurged(AttendanceRecord $record): void
    {
        $this->repo->update($record, [
            'selfie_path' => null,
            'selfie_deleted_at' => now(),
        ]);
    }

    /**
     * HR/Admin correction: edit an existing record (timestamp, lateness,
     * notes). Audit trail captures the change via Auditable trait.
     */
    public function correct(int $recordId, int $actorUserId, array $changes): AttendanceRecord
    {
        return $this->transaction(function () use ($recordId, $actorUserId, $changes) {
            $record = $this->repo->findOrFail($recordId);

            return $this->repo->update($record, array_merge($changes, [
                'corrected_by_user_id' => $actorUserId,
                'corrected_at' => now(),
            ]));
        });
    }

    private function assertNotAlreadyCheckedIn(Employee $employee): void
    {
        $last = $this->repo->lastEventForEmployeeToday(
            (int) $employee->org_id,
            (int) $employee->id,
            $this->cairoDateNow(),
        );

        if ($last && $last->type === 'check_in') {
            throw ValidationException::withMessages([
                'event' => 'Already checked in. Check out before checking in again.',
            ]);
        }
    }

    private function cairoDateNow(): string
    {
        return Carbon::now('Africa/Cairo')->toDateString();
    }

    /**
     * Employees can store shift_start_time as either "09:00" or a Carbon
     * datetime cast to "09:00:00" — normalize to "09:00" for parsing.
     */
    private function normalizeShiftTime(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        return substr((string) $time, 0, 5);
    }
}
