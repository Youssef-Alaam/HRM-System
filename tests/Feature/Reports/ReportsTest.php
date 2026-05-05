<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

// ── Access control ────────────────────────────────────────────────────────────

test('guest is redirected to login', function () {
    $this->get('/reports')->assertRedirect('/login');
});

test('employee can access reports page', function () {
    actingAsRole('employee');
    $this->get('/reports')->assertOk();
});

test('hr can access reports page', function () {
    actingAsRole('hr');
    $this->get('/reports')->assertOk();
});

test('admin can access reports page', function () {
    actingAsRole('admin');
    $this->get('/reports')->assertOk();
});

// ── Own balance ───────────────────────────────────────────────────────────────

test('employee sees their own leave balance', function () {
    $org = Organization::factory()->create();
    $emp = Employee::factory()->create([
        'org_id' => $org->id,
        'annual_leave_balance_days' => 18,
        'sick_leave_balance_days' => 25,
        'casual_leave_balance_days' => 4,
    ]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $response = $this->get('/reports');
    $props = $response->original->getData()['page']['props'];

    expect((int) $props['own_balance']['annual'])->toBe(18);
    expect((int) $props['own_balance']['sick'])->toBe(25);
    expect((int) $props['own_balance']['casual'])->toBe(4);
});

test('user without employee record gets null balance', function () {
    $user = actingAsRole('employee');
    // no employee_id on user

    $response = $this->get('/reports');
    $props = $response->original->getData()['page']['props'];
    expect($props['own_balance'])->toBeNull();
});

// ── Role-gated sections ───────────────────────────────────────────────────────

test('employee does not receive headcount or leave_balances props', function () {
    actingAsRole('employee');
    $response = $this->get('/reports');
    $props = $response->original->getData()['page']['props'];

    expect($props['headcount_by_dept'])->toBeNull();
    expect($props['leave_balances'])->toBeNull();
    expect($props['expiring_docs'])->toBeNull();
});

test('manager receives headcount_by_dept and leave_balances', function () {
    actingAsRole('manager');
    $response = $this->get('/reports');
    $props = $response->original->getData()['page']['props'];

    expect($props['headcount_by_dept'])->not->toBeNull();
    expect($props['leave_balances'])->not->toBeNull();
});

test('manager does not receive expiring_docs (hr/admin only)', function () {
    actingAsRole('manager');
    $response = $this->get('/reports');
    $props = $response->original->getData()['page']['props'];

    expect($props['expiring_docs'])->toBeNull();
    expect($props['headcount_by_status'])->toBeNull();
});

test('hr receives all sections including expiring docs and headcount by status', function () {
    actingAsRole('hr');
    $response = $this->get('/reports');
    $props = $response->original->getData()['page']['props'];

    expect($props['expiring_docs'])->not->toBeNull();
    expect($props['headcount_by_status'])->not->toBeNull();
    expect($props['headcount_by_dept'])->not->toBeNull();
    expect($props['leave_balances'])->not->toBeNull();
});

// ── Headcount data accuracy ───────────────────────────────────────────────────

test('headcount by department returns correct counts', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    $dept = Department::factory()->create(['org_id' => $org->id, 'name' => 'Engineering']);
    Employee::factory()->count(3)->create(['org_id' => $org->id, 'department_id' => $dept->id, 'employment_status' => 'active']);
    Employee::factory()->count(1)->create(['org_id' => $org->id, 'department_id' => $dept->id, 'employment_status' => 'terminated']);

    $response = $this->get('/reports');
    $props = $response->original->getData()['page']['props'];

    $engRow = collect($props['headcount_by_dept'])->firstWhere('department', 'Engineering');
    expect($engRow)->not->toBeNull();
    expect((int) $engRow['total'])->toBe(3); // only active employees
});

// ── Org isolation ─────────────────────────────────────────────────────────────

test('reports only show data from the current org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    actingAsRole('hr', $org);

    $dept = Department::factory()->create(['org_id' => $otherOrg->id, 'name' => 'OtherOrgDept']);
    Employee::factory()->count(5)->create(['org_id' => $otherOrg->id, 'department_id' => $dept->id]);

    $response = $this->get('/reports');
    $props = $response->original->getData()['page']['props'];

    $otherRow = collect($props['headcount_by_dept'])->firstWhere('department', 'OtherOrgDept');
    expect($otherRow)->toBeNull();
});

// ── Date filter ───────────────────────────────────────────────────────────────

test('date filter parameters are passed to props', function () {
    actingAsRole('admin');
    $response = $this->get('/reports?from=2026-01-01&to=2026-03-31');
    $props = $response->original->getData()['page']['props'];

    expect($props['from'])->toBe('2026-01-01');
    expect($props['to'])->toBe('2026-03-31');
});
