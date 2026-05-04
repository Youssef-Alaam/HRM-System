<?php

use App\Models\Employee;
use App\Models\FaceEnrollment;
use App\Services\FaceEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootFaceTest();
});

it('purges descriptor + photos + face_enrollments rows for employees terminated 24+ hours ago', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(),
        'quality_score' => 0.9,
    ])->assertRedirect();

    $disk = Storage::disk(FaceEnrollmentService::STORAGE_DISK);
    $employeeDir = FaceEnrollmentService::STORAGE_DIR.'/'.$employee->id;
    expect($disk->exists($employeeDir))->toBeTrue();

    // Terminate the employee 25h in the past so the purge cutoff catches it.
    $employee->forceFill(['deleted_at' => now()->subHours(25)])->save();

    test()->artisan('face:purge-terminated')->assertExitCode(0);

    expect($disk->exists($employeeDir))->toBeFalse();

    $cleanedEmployee = Employee::withTrashed()->find($employee->id);
    expect($cleanedEmployee->face_descriptor)->toBeNull();

    $enrollment = FaceEnrollment::query()->firstOrFail();
    expect($enrollment->descriptor)->toBe([]);
    expect($enrollment->photo_paths)->toBe([]);
    expect($enrollment->notes)->toContain('Biometric data purged per PDPL');
});

it('leaves recently-terminated employees untouched until 24h has passed', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(),
        'quality_score' => 0.9,
    ])->assertRedirect();

    $employee->forceFill(['deleted_at' => now()->subHours(2)])->save();

    test()->artisan('face:purge-terminated')->assertExitCode(0);

    $disk = Storage::disk(FaceEnrollmentService::STORAGE_DISK);
    $employeeDir = FaceEnrollmentService::STORAGE_DIR.'/'.$employee->id;
    expect($disk->exists($employeeDir))->toBeTrue();
});
