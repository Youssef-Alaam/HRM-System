<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------------
// Helpers
// --------------------------------------------------------------------------

function makeCalEmployee(Organization $org, Office $office, Department $dept, Position $pos, ?int $managerId = null): array
{
    $user = User::factory()->create(['org_id' => $org->id]);
    $emp = Employee::factory()->create([
        'org_id' => $org->id,
        'office_id' => $office->id,
        'department_id' => $dept->id,
        'position_id' => $pos->id,
        'employment_status' => 'active',
        'manager_id' => $managerId,
        'annual_leave_balance_days' => 21,
    ]);
    $user->update(['employee_id' => $emp->id]);

    return [$user, $emp];
}

function approvedLeave(Employee $emp, LeaveType $type, string $start, string $end): LeaveRequest
{
    return LeaveRequest::factory()->create([
        'org_id' => $emp->org_id,
        'employee_id' => $emp->id,
        'leave_type_id' => $type->id,
        'start_date' => $start,
        'end_date' => $end,
        'days_count' => Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1,
        'status' => 'approved',
    ]);
}

function pendingLeave(Employee $emp, LeaveType $type, string $start, string $end): LeaveRequest
{
    return LeaveRequest::factory()->create([
        'org_id' => $emp->org_id,
        'employee_id' => $emp->id,
        'leave_type_id' => $type->id,
        'start_date' => $start,
        'end_date' => $end,
        'days_count' => Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1,
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

    [$this->empUser, $this->empEmp] = makeCalEmployee($this->org, $this->office, $this->dept, $this->pos);
    $this->empUser->assignRole('employee');

    [$this->managerUser, $this->managerEmp] = makeCalEmployee($this->org, $this->office, $this->dept, $this->pos);
    $this->managerUser->assignRole('manager');

    [$this->reporterUser, $this->reporterEmp] = makeCalEmployee(
        $this->org, $this->office, $this->dept, $this->pos, $this->managerEmp->id,
    );
    $this->reporterUser->assignRole('employee');

    [$this->hrUser, $this->hrEmp] = makeCalEmployee($this->org, $this->office, $this->dept, $this->pos);
    $this->hrUser->assignRole('hr');

    $this->annualType = LeaveType::factory()->create([
        'org_id' => $this->org->id,
        'code' => 'annual',
        'name' => 'Annual Leave',
        'is_right_not_discretion' => false,
        'applies_to' => 'all',
    ]);

    // Use a fixed month for deterministic tests
    $this->year = 2026;
    $this->month = 6; // June has 30 days
});

// --------------------------------------------------------------------------
// Auth & access
// --------------------------------------------------------------------------

it('redirects guest to login', function () {
    $this->get('/leave-calendar')->assertRedirect('/login');
});

it('allows employee to access the leave calendar', function () {
    $this->actingAs($this->empUser)
        ->get('/leave-calendar')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('LeaveCalendar/Index'));
});

it('allows manager to access the leave calendar', function () {
    $this->actingAs($this->managerUser)
        ->get('/leave-calendar')
        ->assertOk();
});

it('allows hr to access the leave calendar', function () {
    $this->actingAs($this->hrUser)
        ->get('/leave-calendar')
        ->assertOk();
});

// --------------------------------------------------------------------------
// Month view shape
// --------------------------------------------------------------------------

it('month view generates the correct day count for june', function () {
    $this->actingAs($this->empUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('view', 'month')
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->has('days', 30)
        );
});

it('week view generates 7 days', function () {
    $this->actingAs($this->empUser)
        ->get('/leave-calendar?view=week&year=2026&week=22')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->where('view', 'week')
            ->has('days', 7)
        );
});

// --------------------------------------------------------------------------
// Visibility rules
// --------------------------------------------------------------------------

it('employee sees own approved leave with type detail', function () {
    $req = approvedLeave($this->empEmp, $this->annualType, '2026-06-10', '2026-06-11');

    $this->actingAs($this->empUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('entries', 1)
            ->where('entries.0.employee_id', $this->empEmp->id)
            ->where('entries.0.leave_type_code', 'annual')
        );
});

it('employee sees colleague leave as out-of-office only (no type)', function () {
    approvedLeave($this->reporterEmp, $this->annualType, '2026-06-10', '2026-06-11');

    $this->actingAs($this->empUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('entries', 1)
            ->where('entries.0.employee_id', $this->reporterEmp->id)
            ->where('entries.0.leave_type_code', null)
            ->where('entries.0.leave_type_name', null)
        );
});

it('employee does not see pending leave', function () {
    pendingLeave($this->empEmp, $this->annualType, '2026-06-10', '2026-06-11');

    $this->actingAs($this->empUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('entries', 0));
});

it('manager sees team approved leave with full detail', function () {
    approvedLeave($this->reporterEmp, $this->annualType, '2026-06-10', '2026-06-11');

    $this->actingAs($this->managerUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('entries', 1)
            ->where('entries.0.employee_id', $this->reporterEmp->id)
            ->where('entries.0.leave_type_code', 'annual')
        );
});

it('hr sees all approved leave with full detail', function () {
    approvedLeave($this->empEmp, $this->annualType, '2026-06-10', '2026-06-11');
    approvedLeave($this->reporterEmp, $this->annualType, '2026-06-12', '2026-06-13');

    $this->actingAs($this->hrUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('entries', 2));
});

// --------------------------------------------------------------------------
// Holidays
// --------------------------------------------------------------------------

it('holidays appear in the response', function () {
    Holiday::factory()->create([
        'org_id' => $this->org->id,
        'date' => '2026-06-15',
        'name' => 'Test Holiday',
    ]);

    $this->actingAs($this->empUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('holidays', 1)
            ->where('holidays.0.date', '2026-06-15')
            ->where('holidays.0.name', 'Test Holiday')
        );
});

// --------------------------------------------------------------------------
// HR filter
// --------------------------------------------------------------------------

it('hr filter by department returns only that departments entries', function () {
    $otherDept = Department::factory()->create(['org_id' => $this->org->id]);
    $otherPos = Position::factory()->create(['org_id' => $this->org->id, 'department_id' => $otherDept->id]);
    [$otherUser, $otherEmp] = makeCalEmployee($this->org, $this->office, $otherDept, $otherPos);

    approvedLeave($this->empEmp, $this->annualType, '2026-06-10', '2026-06-11');
    approvedLeave($otherEmp, $this->annualType, '2026-06-12', '2026-06-13');

    $this->actingAs($this->hrUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}&department_id={$this->dept->id}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('entries', 1));
});

// --------------------------------------------------------------------------
// Cross-org isolation
// --------------------------------------------------------------------------

it('does not show leave entries from other orgs', function () {
    $otherOrg = Organization::factory()->create();
    $otherOffice = Office::factory()->create(['org_id' => $otherOrg->id]);
    $otherDept = Department::factory()->create(['org_id' => $otherOrg->id]);
    $otherPos = Position::factory()->create(['org_id' => $otherOrg->id, 'department_id' => $otherDept->id]);
    [$otherUser, $otherEmp] = makeCalEmployee($otherOrg, $otherOffice, $otherDept, $otherPos);
    $otherType = LeaveType::factory()->create(['org_id' => $otherOrg->id, 'code' => 'annual2', 'applies_to' => 'all']);
    approvedLeave($otherEmp, $otherType, '2026-06-10', '2026-06-11');

    $this->actingAs($this->hrUser)
        ->get("/leave-calendar?view=month&year={$this->year}&month={$this->month}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('entries', 0));
});
