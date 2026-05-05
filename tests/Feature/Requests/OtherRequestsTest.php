<?php

use App\Models\Employee;
use App\Models\Organization;
use App\Models\OtherRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

// ── Access control ────────────────────────────────────────────────────────────

test('guest is redirected to login', function () {
    $this->get('/requests')->assertRedirect('/login');
    $this->post('/requests')->assertRedirect('/login');
});

test('all roles can view requests page', function () {
    foreach (['employee', 'hr', 'manager', 'admin'] as $role) {
        actingAsRole($role);
        $this->get('/requests')->assertOk();
    }
});

// ── Submission ────────────────────────────────────────────────────────────────

test('employee with employee record can submit overtime request', function () {
    $org = Organization::factory()->create();

    $emp = Employee::factory()->create(['org_id' => $org->id]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $this->post('/requests', [
        'type' => 'overtime',
        'request_date' => now()->addDay()->format('Y-m-d'),
        'hours_requested' => 1.5,
        'notes' => 'Urgent project deadline.',
    ])->assertRedirect();

    expect(OtherRequest::where('employee_id', $emp->id)->where('type', 'overtime')->exists())->toBeTrue();
});

test('overtime hours are validated against 2hr Art.85 cap', function () {
    $org = Organization::factory()->create();

    $emp = Employee::factory()->create(['org_id' => $org->id]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $this->post('/requests', [
        'type' => 'overtime',
        'request_date' => now()->addDay()->format('Y-m-d'),
        'hours_requested' => 3,
    ])->assertSessionHasErrors('hours_requested');
});

test('employee can submit expense claim', function () {
    $org = Organization::factory()->create();

    $emp = Employee::factory()->create(['org_id' => $org->id]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $this->post('/requests', [
        'type' => 'expense_claim',
        'request_date' => now()->format('Y-m-d'),
        'amount_piasters' => 15000,
        'notes' => 'Taxi for client visit.',
    ])->assertRedirect();

    expect(OtherRequest::where('employee_id', $emp->id)->where('type', 'expense_claim')->where('amount_piasters', 15000)->exists())->toBeTrue();
});

test('expense claim requires amount', function () {
    $org = Organization::factory()->create();

    $emp = Employee::factory()->create(['org_id' => $org->id]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('employee');
    $this->actingAs($user);

    $this->post('/requests', [
        'type' => 'expense_claim',
        'request_date' => now()->format('Y-m-d'),
    ])->assertSessionHasErrors('amount_piasters');
});

test('user without employee record cannot submit request', function () {
    $user = actingAsRole('employee');
    // user has no employee_id set

    $this->post('/requests', [
        'type' => 'change_shift',
        'request_date' => now()->addDay()->format('Y-m-d'),
    ])->assertForbidden();
});

// ── Auto-approve for CEO (NULL manager_id) ────────────────────────────────────

test('request is auto-approved when employee has no manager per Decision 13', function () {
    $org = Organization::factory()->create();

    $emp = Employee::factory()->create(['org_id' => $org->id, 'manager_id' => null]);
    $user = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);
    $user->assignRole('admin');
    $this->actingAs($user);

    $this->post('/requests', [
        'type' => 'overtime',
        'request_date' => now()->addDay()->format('Y-m-d'),
        'hours_requested' => 1,
    ])->assertRedirect();

    $req = OtherRequest::where('employee_id', $emp->id)->first();
    expect($req->status)->toBe('approved');
    expect($req->approved_at)->not->toBeNull();
});

// ── My requests list ──────────────────────────────────────────────────────────

test('my requests tab shows only the current user\'s requests', function () {
    $org = Organization::factory()->create();

    $emp1 = Employee::factory()->create(['org_id' => $org->id]);
    $emp2 = Employee::factory()->create(['org_id' => $org->id]);
    $user1 = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp1->id]);
    $user2 = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp2->id]);
    $user1->assignRole('employee');
    $this->actingAs($user1);

    OtherRequest::create([
        'org_id' => $org->id, 'employee_id' => $emp1->id,
        'type' => 'overtime', 'status' => 'pending',
        'request_date' => now()->format('Y-m-d'), 'hours_requested' => 1,
    ]);

    OtherRequest::create([
        'org_id' => $org->id, 'employee_id' => $emp2->id,
        'type' => 'change_shift', 'status' => 'pending',
        'request_date' => now()->format('Y-m-d'),
    ]);

    $response = $this->get('/requests');
    $props = $response->original->getData()['page']['props'];
    $types = collect($props['mine']['data'])->pluck('type');

    expect($types)->toContain('overtime');
    expect($types)->not->toContain('change_shift');
});

// ── Approval ──────────────────────────────────────────────────────────────────

test('manager can approve a request from their direct report', function () {
    $org = Organization::factory()->create();


    $managerEmp = Employee::factory()->create(['org_id' => $org->id, 'manager_id' => null]);
    $managerUser = User::factory()->create(['org_id' => $org->id, 'employee_id' => $managerEmp->id]);
    $managerUser->assignRole('manager');

    $emp = Employee::factory()->create(['org_id' => $org->id, 'manager_id' => $managerEmp->id]);
    $empUser = User::factory()->create(['org_id' => $org->id, 'employee_id' => $emp->id]);

    $req = OtherRequest::create([
        'org_id' => $org->id, 'employee_id' => $emp->id,
        'type' => 'overtime', 'status' => 'pending',
        'request_date' => now()->format('Y-m-d'), 'hours_requested' => 1,
    ]);

    $this->actingAs($managerUser);
    $this->post("/requests/{$req->id}/approve")->assertRedirect();

    expect($req->fresh()->status)->toBe('approved');
});

test('manager cannot approve a request from an employee outside their team', function () {
    $org = Organization::factory()->create();


    $managerEmp = Employee::factory()->create(['org_id' => $org->id, 'manager_id' => null]);
    $managerUser = User::factory()->create(['org_id' => $org->id, 'employee_id' => $managerEmp->id]);
    $managerUser->assignRole('manager');

    $unrelatedEmp = Employee::factory()->create(['org_id' => $org->id, 'manager_id' => null]);

    $req = OtherRequest::create([
        'org_id' => $org->id, 'employee_id' => $unrelatedEmp->id,
        'type' => 'change_shift', 'status' => 'pending',
        'request_date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($managerUser);
    $this->post("/requests/{$req->id}/approve")->assertForbidden();
});

test('hr can approve any request in the org', function () {
    $org = Organization::factory()->create();

    $hrUser = actingAsRole('hr', $org);
    $emp = Employee::factory()->create(['org_id' => $org->id]);

    $req = OtherRequest::create([
        'org_id' => $org->id, 'employee_id' => $emp->id,
        'type' => 'expense_claim', 'status' => 'pending',
        'request_date' => now()->format('Y-m-d'), 'amount_piasters' => 5000,
    ]);

    $this->post("/requests/{$req->id}/approve")->assertRedirect();
    expect($req->fresh()->status)->toBe('approved');
});

// ── Rejection ─────────────────────────────────────────────────────────────────

test('manager can reject with a reason', function () {
    $org = Organization::factory()->create();


    $managerEmp = Employee::factory()->create(['org_id' => $org->id, 'manager_id' => null]);
    $managerUser = User::factory()->create(['org_id' => $org->id, 'employee_id' => $managerEmp->id]);
    $managerUser->assignRole('manager');

    $emp = Employee::factory()->create(['org_id' => $org->id, 'manager_id' => $managerEmp->id]);

    $req = OtherRequest::create([
        'org_id' => $org->id, 'employee_id' => $emp->id,
        'type' => 'overtime', 'status' => 'pending',
        'request_date' => now()->format('Y-m-d'), 'hours_requested' => 2,
    ]);

    $this->actingAs($managerUser);
    $this->post("/requests/{$req->id}/reject", [
        'rejection_reason' => 'Insufficient business justification.',
    ])->assertRedirect();

    $fresh = $req->fresh();
    expect($fresh->status)->toBe('rejected');
    expect($fresh->rejection_reason)->toBe('Insufficient business justification.');
});

test('rejection reason is required', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);
    $emp = Employee::factory()->create(['org_id' => $org->id]);

    $req = OtherRequest::create([
        'org_id' => $org->id, 'employee_id' => $emp->id,
        'type' => 'change_shift', 'status' => 'pending',
        'request_date' => now()->format('Y-m-d'),
    ]);

    $this->post("/requests/{$req->id}/reject", [
        'rejection_reason' => '',
    ])->assertSessionHasErrors('rejection_reason');
});
