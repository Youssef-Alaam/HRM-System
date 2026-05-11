<?php

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->svc = app(AttendanceService::class);
});

/**
 * Build a fixture: org + office at Tahrir + employee + user.
 *
 * @return array{org: Organization, office: Office, employee: Employee, user: User}
 */
function attendanceFixture(array $opts = []): array
{
    $org = Organization::factory()->create();

    $office = Office::factory()->create([
        'org_id' => $org->id,
        'name' => 'Tahrir HQ',
        'latitude' => $opts['lat'] ?? 30.0444,
        'longitude' => $opts['lng'] ?? 31.2357,
        'allowed_check_in_radius_meters' => $opts['radius'] ?? 100,
    ]);

    $employee = Employee::factory()->create([
        'org_id' => $org->id,
        'shift_start_time' => $opts['shift_start'] ?? '09:00',
        'workweek_days' => $opts['workweek_days'] ?? ['sun', 'mon', 'tue', 'wed', 'thu'],
        'employment_status' => 'active',
    ]);

    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $employee->id]);
    $user->assignRole('employee');

    return compact('org', 'office', 'employee', 'user');
}

// ── Verdict thresholds (Decision 7) ──────────────────────────────────────────

test('verdict score 0.95 maps to verified', function () {
    expect($this->svc->scoreToVerdict(0.95))->toBe('verified');
});

test('verdict score 0.90 boundary maps to verified', function () {
    expect($this->svc->scoreToVerdict(0.90))->toBe('verified');
});

test('verdict score 0.85 maps to possibly_self', function () {
    expect($this->svc->scoreToVerdict(0.85))->toBe('possibly_self');
});

test('verdict score 0.70 boundary maps to possibly_self', function () {
    expect($this->svc->scoreToVerdict(0.70))->toBe('possibly_self');
});

test('verdict score 0.65 maps to unverified', function () {
    expect($this->svc->scoreToVerdict(0.65))->toBe('unverified');
});

test('verdict score 0.00 maps to unverified', function () {
    expect($this->svc->scoreToVerdict(0.00))->toBe('unverified');
});

test('verdict score outside 0-1 range throws', function () {
    $this->svc->scoreToVerdict(1.5);
})->throws(InvalidArgumentException::class);

// ── Check-in: location guards ────────────────────────────────────────────────

test('check-in succeeds when inside the office radius', function () {
    ['employee' => $emp] = attendanceFixture(['radius' => 100]);

    $record = $this->svc->checkIn($emp, [
        'latitude' => 30.0444,   // exactly at office
        'longitude' => 31.2357,
        'verdict_score' => 0.95,
    ]);

    expect($record)->toBeInstanceOf(AttendanceRecord::class);
    expect($record->type)->toBe('check_in');
    expect($record->verdict)->toBe('verified');
});

test('check-in is blocked when outside office radius', function () {
    ['employee' => $emp] = attendanceFixture(['radius' => 100]);

    // ~9.5 km away (Maadi)
    $this->svc->checkIn($emp, [
        'latitude' => 29.9602,
        'longitude' => 31.2569,
        'verdict_score' => 0.95,
    ]);
})->throws(ValidationException::class);

test('check-in error message names the office and distance', function () {
    ['employee' => $emp] = attendanceFixture(['radius' => 100]);

    try {
        $this->svc->checkIn($emp, [
            'latitude' => 29.9602,
            'longitude' => 31.2569,
            'verdict_score' => 0.95,
        ]);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        $msg = $e->errors()['location'][0];
        expect($msg)->toContain('Tahrir HQ');
        expect($msg)->toContain('100m');
    }
});

// ── Check-in: verdict guards ─────────────────────────────────────────────────

test('check-in is blocked when verdict is unverified', function () {
    ['employee' => $emp] = attendanceFixture();

    $this->svc->checkIn($emp, [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.5,  // below 0.70 → unverified
    ]);
})->throws(ValidationException::class);

test('check-in succeeds with possibly_self verdict but flags for HR review', function () {
    ['employee' => $emp] = attendanceFixture();

    $record = $this->svc->checkIn($emp, [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.80,
    ]);

    expect($record->verdict)->toBe('possibly_self');
});

// ── Late detection ───────────────────────────────────────────────────────────

test('check-in within grace window is not late', function () {
    ['employee' => $emp] = attendanceFixture(['shift_start' => '09:00']);

    // 09:04 Cairo (within 5-min grace)
    $eventAt = \Carbon\Carbon::parse(now()->setTimezone('Africa/Cairo')->setTime(9, 4))->setTimezone('UTC');

    expect($this->svc->isLate($emp, $eventAt))->toBeFalse();
});

test('check-in just after grace window is late', function () {
    ['employee' => $emp] = attendanceFixture(['shift_start' => '09:00']);

    // 09:10 Cairo (5 min past grace)
    $eventAt = \Carbon\Carbon::parse(now()->setTimezone('Africa/Cairo')->setTime(9, 10))->setTimezone('UTC');

    expect($this->svc->isLate($emp, $eventAt))->toBeTrue();
});

test('weekend check-in is never late', function () {
    // Workweek: sun-thu, so Friday and Saturday are weekend
    ['employee' => $emp] = attendanceFixture([
        'shift_start' => '09:00',
        'workweek_days' => ['sun', 'mon', 'tue', 'wed', 'thu'],
    ]);

    // Pick a Friday in 2026 (May 1 was a Friday)
    $friday = \Carbon\Carbon::parse('2026-05-01 14:00', 'Africa/Cairo')->setTimezone('UTC');

    expect($this->svc->isLate($emp, $friday))->toBeFalse();
});

// ── Double check-in guard ────────────────────────────────────────────────────

test('cannot check in twice without checking out first', function () {
    ['employee' => $emp] = attendanceFixture();

    $this->svc->checkIn($emp, [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.95,
    ]);

    // Second check-in same day should fail
    $this->svc->checkIn($emp, [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.95,
    ]);
})->throws(ValidationException::class);

// ── Check-out flow ───────────────────────────────────────────────────────────

test('check-out requires prior check-in same day', function () {
    ['employee' => $emp] = attendanceFixture();

    $this->svc->checkOut($emp, [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.95,
    ]);
})->throws(ValidationException::class);

test('check-out succeeds after a check-in', function () {
    ['employee' => $emp] = attendanceFixture();

    $this->svc->checkIn($emp, [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.95,
    ]);

    $out = $this->svc->checkOut($emp, [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.92,
    ]);

    expect($out->type)->toBe('check_out');
    expect($out->is_late)->toBeFalse(); // check-out never marks late
});

// ── Selfie purge ─────────────────────────────────────────────────────────────

test('markSelfiePurged nulls the path and stamps deleted_at', function () {
    ['employee' => $emp] = attendanceFixture();

    $record = $this->svc->checkIn($emp, [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.95,
        'selfie_path' => 'selfies/2026/test.jpg',
    ]);

    expect($record->selfie_path)->toBe('selfies/2026/test.jpg');

    $this->svc->markSelfiePurged($record);

    $fresh = $record->fresh();
    expect($fresh->selfie_path)->toBeNull();
    expect($fresh->selfie_deleted_at)->not->toBeNull();
});
