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

it('lets HR submit 3 photos + descriptor: writes enrollment row, mirrors descriptor on employee', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(0.1),
        'quality_score' => 0.92,
    ])->assertRedirect();

    $enrollment = FaceEnrollment::query()->firstOrFail();
    expect($enrollment->photo_count)->toBe(3);
    expect($enrollment->descriptor)->toHaveCount(128);
    expect((float) $enrollment->descriptor_quality_score)->toEqualWithDelta(0.92, 0.01);
    expect($enrollment->photo_paths)->toHaveCount(3);

    $disk = Storage::disk(FaceEnrollmentService::STORAGE_DISK);
    foreach ($enrollment->photo_paths as $path) {
        $disk->assertExists($path);
    }

    $employee->refresh();
    expect($employee->face_descriptor)->toHaveCount(128);
    expect($employee->last_face_enrollment_at)->not->toBeNull();
    expect((int) $employee->failed_checkin_count)->toBe(0);
    expect((bool) $employee->requires_face_reenrollment)->toBeFalse();
});

it('rejects submission with fewer than 3 photos', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(2),
        'descriptor' => fakeDescriptor(),
        'quality_score' => 0.9,
    ])->assertSessionHasErrors('photos');
});

it('rejects descriptor that is not exactly 128 elements', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => array_fill(0, 64, 0.1),
        'quality_score' => 0.9,
    ])->assertSessionHasErrors('descriptor');
});

it('rejects quality score below the minimum threshold', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(),
        'quality_score' => 0.5,
    ])->assertSessionHasErrors('quality_score');

    expect(FaceEnrollment::count())->toBe(0);
});

it('re-enrollment replaces the active descriptor but preserves history', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(0.10),
        'quality_score' => 0.85,
    ])->assertRedirect();

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(0.20),
        'quality_score' => 0.90,
    ])->assertRedirect();

    expect(FaceEnrollment::count())->toBe(2);
    $latest = FaceEnrollment::query()->latest('enrolled_at')->first();
    expect($latest->descriptor[0])->toEqualWithDelta(0.20, 0.001);

    expect(Employee::find($employee->id)->face_descriptor[0])
        ->toEqualWithDelta(0.20, 0.001);
});
