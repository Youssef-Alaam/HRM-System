<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function bootFaceTest(): void
{
    test()->seed(RolePermissionSeeder::class);
    Storage::fake(\App\Services\FaceEnrollmentService::STORAGE_DISK);
}

function makeFaceOrg(): Organization
{
    return Organization::factory()->create(['name' => 'YZH Solutions']);
}

function makeFaceEmployee(Organization $org, array $overrides = []): Employee
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

function actingAsFaceHr(Organization $org): User
{
    $user = User::factory()->create(['org_id' => $org->id]);
    $user->assignRole(RoleDefinitions::ROLE_HR);
    test()->actingAs($user);

    return $user;
}

function actingAsFaceEmployee(Organization $org, ?Employee $linked = null): User
{
    $user = User::factory()->create([
        'org_id' => $org->id,
        'employee_id' => $linked?->id,
    ]);
    $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);
    test()->actingAs($user);

    return $user;
}

/**
 * @return array<int, UploadedFile>
 */
function fakeEnrollmentPhotos(int $count = 3): array
{
    $photos = [];
    for ($i = 1; $i <= $count; $i++) {
        $photos[] = UploadedFile::fake()->image("photo-{$i}.jpg", 640, 480);
    }

    return $photos;
}

/**
 * @return array<int, float>
 */
function fakeDescriptor(float $value = 0.05): array
{
    return array_fill(0, 128, $value);
}
