<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Dashboard — Feature 1
|--------------------------------------------------------------------------
| Tests written first per CLAUDE.md TDD discipline. Each role gets the
| widgets its permissions allow; nothing leaks. Multi-tenancy verified via
| OrgScope. Empty edge cases covered.
*/

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('dashboard', function () {
    it('renders for an authenticated employee with employee-tier widgets only', function () {
        actingAsRole(RoleDefinitions::ROLE_EMPLOYEE);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('widgets')
                ->has('widgets.identity')
                ->missing('widgets.system_metrics')
                ->missing('widgets.recent_logins')
                ->missing('widgets.headcount'));
    });

    it('shows the headcount widget for HR users', function () {
        actingAsRole(RoleDefinitions::ROLE_HR);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('widgets.headcount')
                ->has('widgets.headcount.total')
                ->has('widgets.department_breakdown'));
    });

    it('shows the headcount widget for managers', function () {
        actingAsRole(RoleDefinitions::ROLE_MANAGER);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('widgets.headcount'));
    });

    it('shows system metrics + recent logins to admins only', function () {
        actingAsRole(RoleDefinitions::ROLE_ADMIN);

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('widgets.system_metrics')
                ->has('widgets.recent_logins'));
    });

    it('does not leak admin widgets to HR users', function () {
        actingAsRole(RoleDefinitions::ROLE_HR);

        $this->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->missing('widgets.system_metrics')
                ->missing('widgets.recent_logins'));
    });

    it('returns headcount scoped to the user org only (multi-tenancy)', function () {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        // 5 active employees in org A, 3 in org B
        $hrA = User::factory()->create(['org_id' => $orgA->id]);
        $hrA->assignRole(RoleDefinitions::ROLE_HR);
        Employee::factory()->count(5)->create([
            'org_id' => $orgA->id,
            'employment_status' => 'active',
        ]);
        Employee::factory()->count(3)->create([
            'org_id' => $orgB->id,
            'employment_status' => 'active',
        ]);

        $this->actingAs($hrA);

        $this->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.headcount.total', 5));
    });

    it('returns 0 headcount for a fresh org with no employees', function () {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['org_id' => $org->id]);
        $admin->assignRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin);

        $this->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.headcount.total', 0));
    });

    it('exposes leave balance to an employee with an employee record', function () {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);

        $employee = Employee::factory()->create([
            'org_id' => $org->id,
            'user_id' => $user->id,
            'annual_leave_balance_days' => 18.5,
            'sick_leave_balance_days' => 8,
            'casual_leave_balance_days' => 5,
        ]);

        $user->employee_id = $employee->id;
        $user->save();

        $this->actingAs($user);

        $this->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('widgets.leave_balance')
                ->where('widgets.leave_balance.annual', 18.5)
                // JSON encodes float 8.0 as 8 — assert int form to match.
                ->where('widgets.leave_balance.sick', 8)
                ->where('widgets.leave_balance.casual', 5));
    });

    it('omits leave balance when user has no employee record', function () {
        actingAsRole(RoleDefinitions::ROLE_EMPLOYEE);
        // actingAsRole creates a user with no Employee row attached.

        $this->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->missing('widgets.leave_balance'));
    });

    it('returns the department breakdown for HR with counts per department', function () {
        $org = Organization::factory()->create();
        $hr = User::factory()->create(['org_id' => $org->id]);
        $hr->assignRole(RoleDefinitions::ROLE_HR);

        $tech = Department::factory()->create(['org_id' => $org->id, 'name' => 'Technical']);
        $sales = Department::factory()->create(['org_id' => $org->id, 'name' => 'Sales']);

        Employee::factory()->count(4)->create([
            'org_id' => $org->id,
            'department_id' => $tech->id,
            'employment_status' => 'active',
        ]);
        Employee::factory()->count(2)->create([
            'org_id' => $org->id,
            'department_id' => $sales->id,
            'employment_status' => 'active',
        ]);

        $this->actingAs($hr);

        $this->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('widgets.department_breakdown', 2)
                ->where('widgets.department_breakdown.0.name', 'Technical')
                ->where('widgets.department_breakdown.0.count', 4)
                ->where('widgets.department_breakdown.1.name', 'Sales')
                ->where('widgets.department_breakdown.1.count', 2));
    });

    it('redirects guests to login', function () {
        $this->get('/dashboard')->assertRedirect('/login');
    });
});

// Partial-reload coverage left to Inertia's own framework tests; what matters
// for this feature is that the headcount widget is exposed to roles that can
// see it (verified above via the per-role tests). The 60s polling is a
// frontend useEffect concern — covered by Cypress/Playwright if we add e2e
// later, not by Pest.
