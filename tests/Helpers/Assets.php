<?php

use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;

/**
 * Shared fixtures for the Assets feature test suite.
 */
function bootAssetsTest(): void
{
    test()->seed(RolePermissionSeeder::class);
}

function makeAssetsOrg(): Organization
{
    return Organization::factory()->create(['name' => 'YZH Solutions']);
}

function makeAssetEmployee(Organization $org, array $overrides = []): Employee
{
    $department = Department::factory()->create(['org_id' => $org->id]);
    $position = Position::factory()->create([
        'org_id' => $org->id,
        'department_id' => $department->id,
    ]);
    $office = Office::factory()->create(['org_id' => $org->id]);

    return Employee::factory()->create(array_merge([
        'org_id' => $org->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'office_id' => $office->id,
    ], $overrides));
}

function makeAssetCategory(Organization $org, string $name = 'Laptop'): AssetCategory
{
    return AssetCategory::create([
        'org_id' => $org->id,
        'name' => $name,
        'icon_name' => 'laptop',
        'is_active' => true,
        'order_index' => 0,
    ]);
}

function actingAsAssetsHr(Organization $org): User
{
    $user = User::factory()->create(['org_id' => $org->id]);
    $user->assignRole(RoleDefinitions::ROLE_HR);
    test()->actingAs($user);

    return $user;
}

function actingAsAssetsAdmin(Organization $org): User
{
    $user = User::factory()->create(['org_id' => $org->id]);
    $user->assignRole(RoleDefinitions::ROLE_ADMIN);
    test()->actingAs($user);

    return $user;
}

function actingAsAssetsManager(Organization $org, ?Employee $linked = null): User
{
    $user = User::factory()->create([
        'org_id' => $org->id,
        'employee_id' => $linked?->id,
    ]);
    $user->assignRole(RoleDefinitions::ROLE_MANAGER);
    test()->actingAs($user);

    return $user;
}

function actingAsAssetsEmployee(Organization $org, ?Employee $linked = null): User
{
    $user = User::factory()->create([
        'org_id' => $org->id,
        'employee_id' => $linked?->id,
    ]);
    $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
    test()->actingAs($user);

    return $user;
}
