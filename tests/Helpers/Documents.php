<?php

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Shared fixtures for the Documents feature test suite.
 */
function bootDocumentsTest(): void
{
    test()->seed(RolePermissionSeeder::class);
    Storage::fake(\App\Services\EmployeeDocumentService::STORAGE_DISK);
}

function makeDocumentsOrg(): Organization
{
    // Bootstrap "YZH Solutions" so the DocumentTypeSeeder finds it.
    return Organization::factory()->create(['name' => 'YZH Solutions']);
}

function seedDocumentTypesFor(Organization $org): void
{
    if (Organization::query()->where('name', 'YZH Solutions')->whereKey($org->id)->doesntExist()) {
        // Seeder hard-codes the org by name; rename for the seed run only.
        $org->update(['name' => 'YZH Solutions']);
    }
    (new DocumentTypeSeeder)->run();
}

function makeEmployeeFor(Organization $org, array $overrides = []): Employee
{
    $department = Department::factory()->create(['org_id' => $org->id]);
    $position = Position::factory()->create(['org_id' => $org->id, 'department_id' => $department->id]);
    $office = Office::factory()->create(['org_id' => $org->id]);

    return Employee::factory()->create(array_merge([
        'org_id' => $org->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'office_id' => $office->id,
    ], $overrides));
}

function actingAsHrIn(Organization $org): User
{
    $user = User::factory()->create(['org_id' => $org->id]);
    $user->assignRole(RoleDefinitions::ROLE_HR);
    test()->actingAs($user);

    return $user;
}

function actingAsAdminIn(Organization $org): User
{
    $user = User::factory()->create(['org_id' => $org->id]);
    $user->assignRole(RoleDefinitions::ROLE_ADMIN);
    test()->actingAs($user);

    return $user;
}

function actingAsEmployeeIn(Organization $org, ?Employee $linked = null): User
{
    $user = User::factory()->create([
        'org_id' => $org->id,
        'employee_id' => $linked?->id,
    ]);
    $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
    test()->actingAs($user);

    return $user;
}

function pdfUploadedFile(string $name = 'doc.pdf', int $kilobytes = 100): UploadedFile
{
    return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
}

function imageUploadedFile(string $name = 'doc.jpg', int $kilobytes = 50): UploadedFile
{
    return UploadedFile::fake()->image($name, 800, 600);
}

function findDocumentType(string $name): ?DocumentType
{
    return DocumentType::query()->where('name', $name)->first();
}
