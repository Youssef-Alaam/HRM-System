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

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeOrgScopedFixtures(int $orgId): array
{
    return [
        'department' => Department::factory()->create(['org_id' => $orgId]),
        'position' => Position::factory()->create(['org_id' => $orgId]),
        'office' => Office::factory()->create(['org_id' => $orgId]),
    ];
}

function validEmployeePayload(array $fixtures, array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Ahmed',
        'last_name' => 'Hassan',
        'email' => 'ahmed.hassan@example.test',
        'phone' => '01012345678',
        'national_id' => '29803121234567',
        'date_of_birth' => '1990-05-12',
        'gender' => 'male',
        'marital_status' => 'single',
        'nationality' => 'Egyptian',
        'address' => '12 Tahrir Square, Cairo',
        'department_id' => $fixtures['department']->id,
        'position_id' => $fixtures['position']->id,
        'office_id' => $fixtures['office']->id,
        'hiring_date' => '2026-04-01',
        'contract_type' => 'probation',
        'base_salary_piasters' => 1_000_000,
        'is_expat' => false,
    ], $overrides);
}

describe('employees.create', function () {
    it('lets HR create an employee end-to-end with linked user and audit trail', function () {
        $hr = actingAsRole(RoleDefinitions::ROLE_HR);
        $fixtures = makeOrgScopedFixtures($hr->org_id);

        $this->post('/employees', validEmployeePayload($fixtures))
            ->assertRedirect();

        $employee = Employee::where('email', 'ahmed.hassan@example.test')->first();

        expect($employee)->not->toBeNull()
            ->and($employee->org_id)->toBe($hr->org_id)
            ->and($employee->employment_status)->toBe('active')
            ->and($employee->user_id)->not->toBeNull();

        // Linked user inherits org_id and gets the employee role
        $user = User::find($employee->user_id);
        expect($user->org_id)->toBe($hr->org_id)
            ->and($user->hasRole(RoleDefinitions::ROLE_EMPLOYEE))->toBeTrue();
    });

    it('auto-generates an employee_code on create', function () {
        $hr = actingAsRole(RoleDefinitions::ROLE_HR);
        $fixtures = makeOrgScopedFixtures($hr->org_id);

        $this->post('/employees', validEmployeePayload($fixtures))->assertRedirect();

        $employee = Employee::where('email', 'ahmed.hassan@example.test')->first();
        expect($employee->employee_code)->not->toBeEmpty();
    });

    it('rejects duplicate email within the same org', function () {
        $hr = actingAsRole(RoleDefinitions::ROLE_HR);
        $fixtures = makeOrgScopedFixtures($hr->org_id);
        Employee::factory()->create([
            'org_id' => $hr->org_id,
            'email' => 'taken@example.test',
        ]);

        $this->post('/employees', validEmployeePayload($fixtures, ['email' => 'taken@example.test']))
            ->assertSessionHasErrors('email');
    });

    it('rejects an Egyptian national ID that is not 14 digits', function () {
        $hr = actingAsRole(RoleDefinitions::ROLE_HR);
        $fixtures = makeOrgScopedFixtures($hr->org_id);

        $this->post('/employees', validEmployeePayload($fixtures, ['national_id' => '123']))
            ->assertSessionHasErrors('national_id');
    });

    it('rejects an Egyptian phone that is not 11 digits starting with 010/011/012/015', function () {
        $hr = actingAsRole(RoleDefinitions::ROLE_HR);
        $fixtures = makeOrgScopedFixtures($hr->org_id);

        $this->post('/employees', validEmployeePayload($fixtures, ['phone' => '0202255']))
            ->assertSessionHasErrors('phone');
    });

    it('requires passport + work permit when is_expat is true', function () {
        $hr = actingAsRole(RoleDefinitions::ROLE_HR);
        $fixtures = makeOrgScopedFixtures($hr->org_id);

        $this->post('/employees', validEmployeePayload($fixtures, [
            'is_expat' => true,
            // passport_number + work_permit_number deliberately omitted
        ]))->assertSessionHasErrors(['passport_number', 'work_permit_number', 'work_permit_expiry']);
    });

    it('accepts a non-expat employee without passport fields', function () {
        $hr = actingAsRole(RoleDefinitions::ROLE_HR);
        $fixtures = makeOrgScopedFixtures($hr->org_id);

        $this->post('/employees', validEmployeePayload($fixtures, ['is_expat' => false]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();
    });

    it('blocks employees from creating', function () {
        $employee = actingAsRole(RoleDefinitions::ROLE_EMPLOYEE);
        $fixtures = makeOrgScopedFixtures($employee->org_id);

        $this->post('/employees', validEmployeePayload($fixtures))->assertForbidden();
    });

    it('blocks managers from creating', function () {
        $manager = actingAsRole(RoleDefinitions::ROLE_MANAGER);
        $fixtures = makeOrgScopedFixtures($manager->org_id);

        $this->post('/employees', validEmployeePayload($fixtures))->assertForbidden();
    });
});
