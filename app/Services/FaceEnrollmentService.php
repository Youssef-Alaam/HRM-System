<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\FaceEnrollment;
use App\Repositories\Contracts\FaceEnrollmentRepositoryInterface;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Layer-3 service for the face-enrollment feature.
 *
 * Locked 2026-04-30 with Walid:
 * - 3 photos at enrollment, computed by face-api.js client-side.
 * - Client sends the canonical 128-element descriptor + a quality score;
 *   server stores the photos + descriptor + scores. We trust the
 *   FormRequest's validation of shape; the descriptor is the user's
 *   biometric so we never re-derive it server-side.
 * - 12-month re-enrollment cadence.
 * - 5 consecutive failed check-ins → forces re-enrollment.
 * - PDPL: descriptor + photos auto-purge 24h after termination.
 */
class FaceEnrollmentService extends BaseService
{
    public const STORAGE_DISK = 'local';

    public const STORAGE_DIR = 'employee-faces';

    public const PHOTO_COUNT = 3;

    public const MIN_QUALITY_SCORE = 0.70;

    public const REENROLLMENT_INTERVAL_MONTHS = 12;

    public const FAILED_CHECKIN_THRESHOLD = 5;

    public const POST_TERMINATION_PURGE_HOURS = 24;

    public function __construct(
        private readonly FaceEnrollmentRepositoryInterface $repo,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $photos
     * @param  array<int, float>  $descriptor  128-element vector
     */
    public function submit(
        Employee $employee,
        array $photos,
        array $descriptor,
        float $qualityScore,
        int $enrolledByUserId,
        ?string $notes = null,
    ): FaceEnrollment {
        return $this->transaction(function () use (
            $employee, $photos, $descriptor, $qualityScore, $enrolledByUserId, $notes,
        ) {
            if (count($photos) !== self::PHOTO_COUNT) {
                throw new DomainException(
                    'Exactly '.self::PHOTO_COUNT.' photos are required for enrollment.',
                );
            }
            if (count($descriptor) !== 128) {
                throw new DomainException('Descriptor must be a 128-element vector.');
            }
            if ($qualityScore < self::MIN_QUALITY_SCORE) {
                throw new DomainException(
                    'Quality score too low — retake one or more photos.',
                );
            }

            $enrollmentId = (string) Str::uuid();
            $relativeDir = self::STORAGE_DIR."/{$employee->id}/{$enrollmentId}";
            $photoPaths = [];
            foreach ($photos as $index => $file) {
                $photoPaths[] = $file->storeAs(
                    $relativeDir,
                    sprintf('photo-%d.jpg', $index + 1),
                    self::STORAGE_DISK,
                );
            }

            $enrollment = $this->repo->create([
                'org_id' => $employee->org_id,
                'employee_id' => $employee->id,
                'enrolled_at' => CarbonImmutable::now(),
                'enrolled_by_user_id' => $enrolledByUserId,
                'photo_count' => self::PHOTO_COUNT,
                'descriptor_quality_score' => $qualityScore,
                'descriptor' => $descriptor,
                'photo_paths' => $photoPaths,
                'notes' => $notes,
            ]);

            // Mirror the canonical descriptor onto the employee row for
            // hot-path read at attendance check-in time, and reset the
            // 12-month + failed-checkin clocks.
            $employee->forceFill([
                'face_descriptor' => $descriptor,
                'last_face_enrollment_at' => $enrollment->enrolled_at,
                'failed_checkin_count' => 0,
                'requires_face_reenrollment' => false,
            ])->save();

            return $enrollment;
        });
    }

    /**
     * HR-driven force-reset: clears the active descriptor and failed-checkin
     * counter so the employee must re-enroll before the next check-in.
     * Does NOT delete the face_enrollments history rows — those stay for
     * audit + PDPL.
     */
    public function reset(Employee $employee): void
    {
        $this->transaction(function () use ($employee) {
            $employee->forceFill([
                'face_descriptor' => null,
                'failed_checkin_count' => 0,
                'requires_face_reenrollment' => true,
            ])->save();
        });
    }

    /**
     * Called by Feature 5 (Attendance) on each face-mismatch at check-in.
     * Bumps the counter; flips the re-enrollment flag at the threshold.
     */
    public function recordFailedCheckin(Employee $employee): void
    {
        $next = (int) $employee->failed_checkin_count + 1;
        $employee->failed_checkin_count = $next;
        if ($next >= self::FAILED_CHECKIN_THRESHOLD) {
            $employee->requires_face_reenrollment = true;
        }
        $employee->save();
    }

    /**
     * Called by Feature 5 on a successful check-in to reset the counter.
     */
    public function recordSuccessfulCheckin(Employee $employee): void
    {
        if ((int) $employee->failed_checkin_count !== 0) {
            $employee->failed_checkin_count = 0;
            $employee->save();
        }
    }

    /**
     * PDPL purge: wipes descriptor + photos for an employee whose
     * deleted_at was set ≥24h ago. Called by the daily scheduled command.
     * Audit trail (face_enrollments rows) is preserved but with
     * descriptor + photo_paths blanked so no biometric data persists.
     */
    public function purgeForTerminatedEmployee(Employee $employee): void
    {
        $this->transaction(function () use ($employee) {
            $disk = Storage::disk(self::STORAGE_DISK);
            $employeeDir = self::STORAGE_DIR.'/'.$employee->id;

            if ($disk->exists($employeeDir)) {
                $disk->deleteDirectory($employeeDir);
            }

            FaceEnrollment::query()
                ->where('employee_id', $employee->id)
                ->each(function (FaceEnrollment $enrollment) {
                    $enrollment->forceFill([
                        'descriptor' => [],
                        'photo_paths' => [],
                        'notes' => trim(
                            ($enrollment->notes ? $enrollment->notes."\n\n" : '').
                            'Biometric data purged per PDPL '.now()->toIso8601String().'.',
                        ),
                    ])->save();
                });

            $employee->forceFill([
                'face_descriptor' => null,
            ])->save();
        });
    }
}
