<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------------
// Helpers
// --------------------------------------------------------------------------

function makeEmployee7(Organization $org, Office $office, Department $dept, Position $pos, ?int $managerId = null, array $extra = []): array
{
    $user = User::factory()->create(['org_id' => $org->id]);
    $emp = Employee::factory()->create(array_merge([
        'org_id' => $org->id,
        'office_id' => $office->id,
        'department_id' => $dept->id,
        'position_id' => $pos->id,
        'employment_status' => 'active',
        'manager_id' => $managerId,
        'annual_leave_balance_days' => 21,
        'sick_leave_balance_days' => 30,
        'casual_leave_balance_days' => 7,
    ], $extra));
    $user->update(['employee_id' => $emp->id]);

    return [$user, $emp];
}

function pendingRequest7(Employee $emp, LeaveType $type, int $days = 2): LeaveRequest
{
    $start = now()->addWeeks(2)->startOfWeek()->next(\Carbon\Carbon::SUNDAY)->format('Y-m-d');
    $end = \Carbon\Carbon::parse($start)->addDays($days - 1)->format('Y-m-d');

    return LeaveRequest::factory()->create([
        'org_id' => $emp->org_id,
        'employee_id' => $emp->id,
        'leave_type_id' => $type->id,
        'start_date' => $start,
        'end_date' => $end,
        'days_count' => $days,
        'status' => 'pending',
    ]);
}

// --------------------------------------------------------------------------
// Setup
// --------------------------------------------------------------------------

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->org = Organization::factory()->create();
    $this->office = Office::factory()->create(['org_id' => $this->org->id]);
    $this->dept = Department::factory()->create(['org_id' => $this->org->id]);
    $this->pos = Position::factory()->create([
        'org_id' => $this->org->id,
        'department_id' => $this->dept->id,
    ]);

    [$this->managerUser, $this->managerEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos);
    $this->managerUser->assignRole('manager');

    [$this->reporterUser, $this->reporterEmp] = makeEmployee7(
        $this->org, $this->office, $this->dept, $this->pos,
        $this->managerEmp->id,
    );
    $this->reporterUser->assignRole('employee');

    [$this->hrUser, $this->hrEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos);
    $this->hrUser->assignRole('hr');

    [$this->employeeUser, $this->employeeEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos);
    $this->employeeUser->assignRole('employee');

    $this->annualType = LeaveType::factory()->create([
        'org_id' => $this->org->id,
        'code' => 'annual',
        'is_right_not_discretion' => false,
        'applies_to' => 'all',
    ]);
    $this->sickType = LeaveType::factory()->create([
        'org_id' => $this->org->id,
        'code' => 'sick',
        'is_right_not_discretion' => true,
        'applies_to' => 'all',
    ]);
});

// --------------------------------------------------------------------------
// Auth & access
// --------------------------------------------------------------------------

it('redirects guest to login', function () {
    $this->get('/approvals')->assertRedirect('/login');
});

it('blocks employee with no approval permission', function () {
    $this->actingAs($this->employeeUser)
        ->get('/approvals')
        ->assertStatus(403);
});

it('allows manager to access approvals page', function () {
    $this->actingAs($this->managerUser)
        ->get('/approvals')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Approvals/Index'));
});

it('allows hr to access approvals page', function () {
    $this->actingAs($this->hrUser)
        ->get('/approvals')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Approvals/Index'));
});

// --------------------------------------------------------------------------
// Manager view
// --------------------------------------------------------------------------

it('shows manager only their team pending requests', function () {
    $req = pendingRequest7($this->reporterEmp, $this->annualType);

    $this->actingAs($this->managerUser)
        ->get('/approvals')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $req->id)
        );
});

it('does not show manager requests from other teams', function () {
    // Different manager's reporter — not in $this->managerEmp's team
    [$otherMgrUser, $otherMgrEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos);
    [$otherRepUser, $otherRepEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos, $otherMgrEmp->id);
    pendingRequest7($otherRepEmp, $this->annualType);

    $this->actingAs($this->managerUser)
        ->get('/approvals')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('requests.data', 0));
});

it('does not show manager non-pending requests', function () {
    LeaveRequest::factory()->create([
        'org_id' => $this->org->id,
        'employee_id' => $this->reporterEmp->id,
        'leave_type_id' => $this->annualType->id,
        'status' => 'approved',
    ]);

    $this->actingAs($this->managerUser)
        ->get('/approvals')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('requests.data', 0));
});

// --------------------------------------------------------------------------
// HR view
// --------------------------------------------------------------------------

it('shows hr all pending requests for the org', function () {
    pendingRequest7($this->reporterEmp, $this->annualType);

    [$otherMgrUser, $otherMgrEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos);
    [$otherRepUser, $otherRepEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos, $otherMgrEmp->id);
    pendingRequest7($otherRepEmp, $this->sickType);

    $this->actingAs($this->hrUser)
        ->get('/approvals')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('requests.data', 2));
});

// --------------------------------------------------------------------------
// Approve
// --------------------------------------------------------------------------

it('allows manager to approve their team pending request', function () {
    $req = pendingRequest7($this->reporterEmp, $this->annualType, 3);
    $balanceBefore = (float) $this->reporterEmp->fresh()->annual_leave_balance_days;

    $this->actingAs($this->managerUser)
        ->post("/approvals/{$req->id}/approve")
        ->assertRedirect();

    $req->refresh();
    expect($req->status)->toBe('approved');
    expect($req->approved_by_user_id)->toBe($this->managerUser->id);
    expect($req->approved_at)->not->toBeNull();

    $balanceAfter = (float) $this->reporterEmp->fresh()->annual_leave_balance_days;
    expect($balanceAfter)->toBe($balanceBefore - 3);
});

it('prevents manager from approving non-team request', function () {
    [$otherMgrUser, $otherMgrEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos);
    [$otherRepUser, $otherRepEmp] = makeEmployee7($this->org, $this->office, $this->dept, $this->pos, $otherMgrEmp->id);
    $req = pendingRequest7($otherRepEmp, $this->annualType);

    $this->actingAs($this->managerUser)
        ->post("/approvals/{$req->id}/approve")
        ->assertStatus(403);
});

it('allows hr to approve any pending request', function () {
    $req = pendingRequest7($this->reporterEmp, $this->annualType, 2);

    $this->actingAs($this->hrUser)
        ->post("/approvals/{$req->id}/approve")
        ->assertRedirect();

    expect($req->fresh()->status)->toBe('approved');
});

it('returns an error when approving an already-approved request', function () {
    $req = LeaveRequest::factory()->create([
        'org_id' => $this->org->id,
        'employee_id' => $this->reporterEmp->id,
        'leave_type_id' => $this->annualType->id,
        'status' => 'approved',
    ]);

    $this->actingAs($this->hrUser)
        ->post("/approvals/{$req->id}/approve")
        ->assertSessionHasErrors('status');
});

// --------------------------------------------------------------------------
// Reject
// --------------------------------------------------------------------------

it('allows manager to reject annual leave with valid reason', function () {
    $req = pendingRequest7($this->reporterEmp, $this->annualType);

    $this->actingAs($this->managerUser)
        ->post("/approvals/{$req->id}/reject", ['rejected_reason' => 'Not enough coverage during that period.'])
        ->assertRedirect();

    $req->refresh();
    expect($req->status)->toBe('rejected');
    expect($req->rejected_reason)->toBe('Not enough coverage during that period.');
});

it('blocks manager from rejecting rights-based leave', function () {
    $req = pendingRequest7($this->reporterEmp, $this->sickType);

    $this->actingAs($this->managerUser)
        ->post("/approvals/{$req->id}/reject", ['rejected_reason' => 'Fraud suspicion, need HR.'])
        ->assertSessionHasErrors('rejected_reason');

    expect($req->fresh()->status)->toBe('pending');
});

it('allows hr to reject rights-based leave', function () {
    $req = pendingRequest7($this->reporterEmp, $this->sickType);

    $this->actingAs($this->hrUser)
        ->post("/approvals/{$req->id}/reject", ['rejected_reason' => 'Missing required certificate documentation.'])
        ->assertRedirect();

    expect($req->fresh()->status)->toBe('rejected');
});

it('rejects with validation error when reason is too short', function () {
    $req = pendingRequest7($this->reporterEmp, $this->annualType);

    $this->actingAs($this->managerUser)
        ->post("/approvals/{$req->id}/reject", ['rejected_reason' => 'No'])
        ->assertSessionHasErrors('rejected_reason');
});

// --------------------------------------------------------------------------
// ANA-3.18 – approver cascade on terminated approver
// --------------------------------------------------------------------------

it('moves terminated manager reports to hr queue', function () {
    $req = pendingRequest7($this->reporterEmp, $this->annualType);

    // Soft-delete the manager employee (simulate termination)
    $this->managerEmp->delete();

    // Manager no longer has active team members in their queue
    $this->actingAs($this->managerUser)
        ->get('/approvals')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('requests.data', 0));

    // HR sees the orphaned request
    $this->actingAs($this->hrUser)
        ->get('/approvals')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('requests.data', 1));
});
