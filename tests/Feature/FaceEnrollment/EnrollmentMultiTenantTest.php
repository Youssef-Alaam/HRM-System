<?php

use App\Models\FaceEnrollment;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootFaceTest();
});

it('OrgScope hides face_enrollments rows from another org', function () {
    $orgA = makeFaceOrg();
    $orgB = Organization::factory()->create(['name' => 'OtherCorp']);

    actingAsFaceHr($orgA);
    $employeeA = makeFaceEmployee($orgA);
    test()->post("/employees/{$employeeA->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(),
        'quality_score' => 0.9,
    ])->assertRedirect();

    actingAsFaceHr($orgB);
    expect(FaceEnrollment::count())->toBe(0); // org-scoped query — no leakage
});
