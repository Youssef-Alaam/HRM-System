<?php

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('guest is redirected to login on the attendance page', function () {
    $this->get('/attendance')->assertRedirect('/login');
});

test('employee with employee_id can access attendance page', function () {
    $org = Organization::factory()->create();
    $emp = Employee::factory()->create(['org_id' => $org->id]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $this->get('/attendance')->assertOk();
});

test('user without employee link gets has_employee=false', function () {
    $user = actingAsRole('employee');

    $response = $this->get('/attendance');
    $props = $response->original->getData()['page']['props'];

    expect($props['has_employee'])->toBeFalse();
});

test('check-in endpoint creates an AttendanceRecord', function () {
    $org = Organization::factory()->create();
    Office::factory()->create([
        'org_id' => $org->id,
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'allowed_check_in_radius_meters' => 200,
    ]);
    $emp = Employee::factory()->create(['org_id' => $org->id, 'employment_status' => 'active']);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $response = $this->postJson('/attendance/check-in', [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.95,
    ]);

    $response->assertCreated();
    expect(AttendanceRecord::where('employee_id', $emp->id)->where('type', 'check_in')->exists())->toBeTrue();
});

test('check-in endpoint validates required fields', function () {
    $org = Organization::factory()->create();
    $emp = Employee::factory()->create(['org_id' => $org->id]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $response = $this->postJson('/attendance/check-in', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['latitude', 'longitude', 'verdict_score']);
});

test('check-in endpoint returns 422 when outside radius', function () {
    $org = Organization::factory()->create();
    Office::factory()->create([
        'org_id' => $org->id,
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'allowed_check_in_radius_meters' => 50,
    ]);
    $emp = Employee::factory()->create(['org_id' => $org->id, 'employment_status' => 'active']);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $response = $this->postJson('/attendance/check-in', [
        'latitude' => 29.9602,  // ~9.5 km away
        'longitude' => 31.2569,
        'verdict_score' => 0.95,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('location');
});

test('user without employee link cannot check in', function () {
    actingAsRole('employee'); // no employee_id

    $response = $this->postJson('/attendance/check-in', [
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'verdict_score' => 0.95,
    ]);

    $response->assertForbidden();
});

// ── Admin view ───────────────────────────────────────────────────────────────

test('employee cannot access admin attendance view', function () {
    actingAsRole('employee');
    $this->get('/admin/attendance')->assertForbidden();
});

test('hr can access admin attendance view', function () {
    actingAsRole('hr');
    $this->get('/admin/attendance')->assertOk();
});

test('hr can correct an attendance record', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);
    $emp = Employee::factory()->create(['org_id' => $org->id]);

    $record = AttendanceRecord::create([
        'org_id' => $org->id,
        'employee_id' => $emp->id,
        'type' => 'check_in',
        'event_at' => now(),
        'event_date' => now()->toDateString(),
        'is_late' => true,
        'verdict' => 'verified',
        'verdict_score' => 0.95,
    ]);

    $this->patch("/admin/attendance/{$record->id}", [
        'is_late' => false,
        'notes' => 'Train delay confirmed by manager.',
    ])->assertRedirect();

    $fresh = $record->fresh();
    expect($fresh->is_late)->toBeFalse();
    expect($fresh->notes)->toBe('Train delay confirmed by manager.');
    expect($fresh->corrected_by_user_id)->not->toBeNull();
});

// ── Face descriptor endpoint ─────────────────────────────────────────────────

test('my-descriptor returns enrolled=false when employee has no face_descriptor', function () {
    $org = Organization::factory()->create();
    $emp = Employee::factory()->create(['org_id' => $org->id, 'face_descriptor' => null]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $response = $this->getJson('/attendance/my-descriptor');
    $response->assertOk();
    $response->assertJson(['enrolled' => false, 'descriptor' => null]);
});

test('my-descriptor returns the descriptor array when enrolled', function () {
    $org = Organization::factory()->create();
    $descriptor = array_fill(0, 128, 0.1); // face-api descriptors are 128-d
    $emp = Employee::factory()->create([
        'org_id' => $org->id,
        'face_descriptor' => $descriptor,
    ]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $response = $this->getJson('/attendance/my-descriptor');
    $response->assertOk();
    $response->assertJson(['enrolled' => true]);
    expect($response->json('descriptor'))->toBeArray();
    expect(count($response->json('descriptor')))->toBe(128);
});

test('my-descriptor returns null for user without employee_id', function () {
    actingAsRole('employee'); // no employee_id

    $response = $this->getJson('/attendance/my-descriptor');
    $response->assertOk();
    $response->assertJson(['enrolled' => false]);
});

// ── Org isolation ────────────────────────────────────────────────────────────

test('attendance records are scoped by org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    actingAsRole('hr', $org);

    // Foreign org record
    $foreignEmp = Employee::factory()->create(['org_id' => $otherOrg->id]);
    AttendanceRecord::withoutGlobalScopes()->create([
        'org_id' => $otherOrg->id,
        'employee_id' => $foreignEmp->id,
        'type' => 'check_in',
        'event_at' => now(),
        'event_date' => now()->toDateString(),
        'verdict' => 'verified',
        'verdict_score' => 0.95,
    ]);

    $response = $this->get('/admin/attendance');
    $props = $response->original->getData()['page']['props'];

    // No records from the other org should appear in our view
    expect($props['records']['total'])->toBe(0);
});
