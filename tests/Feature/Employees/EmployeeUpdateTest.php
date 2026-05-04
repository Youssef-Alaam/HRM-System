<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Position;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Tier-aware update coverage (locked 2026-04-30 with Walid as the
 * simplified 2-tier model). Self-tier fields are editable by the
 * employee themselves; HR-only fields require employees.edit.any.
 */

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeOrgEmployee(int $orgId, array $overrides = []): Employee
{
    $department = Department::factory()->create(['org_id' => $orgId]);
    $position = Position::factory()->create(['org_id' => $orgId, 'department_id' => $department->id]);
    $office = Office::factory()->create(['org_id' => $orgId]);

    return Employee::factory()->create(array_merge([
        'org_id' => $orgId,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'office_id' => $office->id,
    ], $overrides));
}

describe('employees.update — Self-tier fields', function () {
    it('lets the employee update their own Tier 1 fields directly', function () {
        $org = \App\Models\Organization::factory()->create();
        $employee = makeOrgEmployee($org->id);

        $user = User::factory()->create([
            'org_id' => $org->id,
            'employee_id' => $employee->id,
        ]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($user);

        $this->patch("/employees/{$employee->id}", [
            'phone' => '01098765432',
            'address' => '12 New Street, Cairo',
            'emergency_contact_name' => 'Dad',
            'emergency_contact_phone' => '01055555555',
            'marital_status' => 'married',
            'dependents' => 2,
        ])->assertRedirect();

        $fresh = $employee->fresh();
        expect($fresh->phone)->toBe('01098765432')
            ->and($fresh->address)->toBe('12 New Street, Cairo')
            ->and($fresh->emergency_contact_name)->toBe('Dad')
            ->and($fresh->emergency_contact_phone)->toBe('01055555555')
            ->and($fresh->marital_status)->toBe('married')
            ->and($fresh->dependents)->toBe(2);
    });

    it('redirects self-edits back to /profile so the form context is preserved', function () {
        $org = \App\Models\Organization::factory()->create();
        $employee = makeOrgEmployee($org->id);

        $user = User::factory()->create([
            'org_id' => $org->id,
            'employee_id' => $employee->id,
        ]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($user);

        $this->patch("/employees/{$employee->id}", ['phone' => '01098765432'])
            ->assertRedirect('/profile');
    });

    it('rejects an employee editing someone elses Tier 1 fields', function () {
        $org = \App\Models\Organization::factory()->create();
        $self = makeOrgEmployee($org->id);
        $other = makeOrgEmployee($org->id);

        $user = User::factory()->create([
            'org_id' => $org->id,
            'employee_id' => $self->id,
        ]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($user);

        $this->patch("/employees/{$other->id}", ['phone' => '01098765432'])
            ->assertForbidden();
    });

    it('rejects an invalid Egyptian phone format on Tier 1 update', function () {
        $org = \App\Models\Organization::factory()->create();
        $employee = makeOrgEmployee($org->id);

        $user = User::factory()->create([
            'org_id' => $org->id,
            'employee_id' => $employee->id,
        ]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($user);

        $this->patch("/employees/{$employee->id}", ['phone' => '0202'])
            ->assertSessionHasErrors('phone');
    });
});

describe('employees.update — HR-only-tier fields', function () {
    it('blocks an employee from editing their own salary', function () {
        $org = \App\Models\Organization::factory()->create();
        $employee = makeOrgEmployee($org->id, ['base_salary_piasters' => 1_500_000]);

        $user = User::factory()->create([
            'org_id' => $org->id,
            'employee_id' => $employee->id,
        ]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($user);

        $this->patch("/employees/{$employee->id}", [
            'base_salary_piasters' => 9_999_999,
        ])->assertForbidden();

        expect($employee->fresh()->base_salary_piasters)->toBe(1_500_000);
    });

    it('blocks an employee from changing their own department', function () {
        $org = \App\Models\Organization::factory()->create();
        $employee = makeOrgEmployee($org->id);
        $newDept = Department::factory()->create(['org_id' => $org->id]);

        $user = User::factory()->create([
            'org_id' => $org->id,
            'employee_id' => $employee->id,
        ]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($user);

        $this->patch("/employees/{$employee->id}", [
            'department_id' => $newDept->id,
        ])->assertForbidden();
    });

    it('blocks an employee from rewriting their own employee_code', function () {
        $org = \App\Models\Organization::factory()->create();
        $employee = makeOrgEmployee($org->id);
        $original = $employee->employee_code;

        $user = User::factory()->create([
            'org_id' => $org->id,
            'employee_id' => $employee->id,
        ]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($user);

        $this->patch("/employees/{$employee->id}", [
            'employee_code' => 'EMP-99999',
        ])->assertForbidden();

        expect($employee->fresh()->employee_code)->toBe($original);
    });

    it('lets HR update HR-only fields on any employee in their org', function () {
        $org = \App\Models\Organization::factory()->create();
        $employee = makeOrgEmployee($org->id, ['base_salary_piasters' => 1_500_000]);

        $hr = User::factory()->create(['org_id' => $org->id]);
        $hr->assignRole(RoleDefinitions::ROLE_HR);
        $this->actingAs($hr);

        $this->patch("/employees/{$employee->id}", [
            'base_salary_piasters' => 2_500_000,
        ])->assertRedirect();

        expect($employee->fresh()->base_salary_piasters)->toBe(2_500_000);
    });

    it('lets HR mix HR-only and Self-tier fields in a single update', function () {
        $org = \App\Models\Organization::factory()->create();
        $employee = makeOrgEmployee($org->id);

        $hr = User::factory()->create(['org_id' => $org->id]);
        $hr->assignRole(RoleDefinitions::ROLE_HR);
        $this->actingAs($hr);

        $this->patch("/employees/{$employee->id}", [
            'phone' => '01098765432',
            'base_salary_piasters' => 2_500_000,
        ])->assertRedirect();

        $fresh = $employee->fresh();
        expect($fresh->phone)->toBe('01098765432')
            ->and($fresh->base_salary_piasters)->toBe(2_500_000);
    });
});

describe('employees.create — form route', function () {
    it('renders the create form for HR with org-scoped picker data', function () {
        $org = \App\Models\Organization::factory()->create();
        Department::factory()->count(2)->create(['org_id' => $org->id]);

        $hr = User::factory()->create(['org_id' => $org->id]);
        $hr->assignRole(RoleDefinitions::ROLE_HR);
        $this->actingAs($hr);

        $this->get('/employees/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Employees/Create')
                ->has('departments', 2)
                ->has('positions')
                ->has('offices')
                ->has('managers'));
    });

    it('blocks employees from the create form', function () {
        $org = \App\Models\Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id]);
        $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($user);

        $this->get('/employees/create')->assertForbidden();
    });

    it('blocks managers from the create form', function () {
        $org = \App\Models\Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id]);
        $user->assignRole(RoleDefinitions::ROLE_MANAGER);
        $this->actingAs($user);

        $this->get('/employees/create')->assertForbidden();
    });

    it('store path now accepts the dependents field', function () {
        $org = \App\Models\Organization::factory()->create();
        $department = Department::factory()->create(['org_id' => $org->id]);
        $position = Position::factory()->create(['org_id' => $org->id, 'department_id' => $department->id]);
        $office = Office::factory()->create(['org_id' => $org->id]);

        $hr = User::factory()->create(['org_id' => $org->id]);
        $hr->assignRole(RoleDefinitions::ROLE_HR);
        $this->actingAs($hr);

        $this->post('/employees', [
            'first_name' => 'Layla',
            'last_name' => 'Hassan',
            'email' => 'layla.h@example.test',
            'phone' => '01012345678',
            'national_id' => '29803121234567',
            'date_of_birth' => '1992-05-12',
            'gender' => 'female',
            'marital_status' => 'married',
            'dependents' => 3,
            'nationality' => 'Egyptian',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'office_id' => $office->id,
            'hiring_date' => '2026-04-01',
            'contract_type' => 'unlimited',
            'base_salary_piasters' => 1_500_000,
            'is_expat' => false,
        ])->assertRedirect();

        expect(Employee::where('email', 'layla.h@example.test')->first()->dependents)->toBe(3);
    });
});
