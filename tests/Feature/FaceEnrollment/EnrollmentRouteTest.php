<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootFaceTest();
});

it('blocks employees and managers from submitting an enrollment', function () {
    $org = makeFaceOrg();
    $employee = makeFaceEmployee($org);

    actingAsFaceEmployee($org, $employee);
    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(),
        'quality_score' => 0.9,
    ])->assertForbidden();

    $manager = \App\Models\User::factory()->create(['org_id' => $org->id]);
    $manager->assignRole(\App\Permissions\RoleDefinitions::ROLE_MANAGER);
    test()->actingAs($manager);
    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(),
        'quality_score' => 0.9,
    ])->assertForbidden();
});

it('blocks employees from triggering reset', function () {
    $org = makeFaceOrg();
    $employee = makeFaceEmployee($org);

    actingAsFaceEmployee($org, $employee);
    test()->post("/employees/{$employee->id}/face-enrollment/reset")
        ->assertForbidden();
});

it('lets HR reset an employee enrollment — clears descriptor + flips flag', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    test()->post("/employees/{$employee->id}/face-enrollment", [
        'photos' => fakeEnrollmentPhotos(),
        'descriptor' => fakeDescriptor(),
        'quality_score' => 0.9,
    ])->assertRedirect();

    test()->post("/employees/{$employee->id}/face-enrollment/reset")
        ->assertRedirect();

    $fresh = $employee->fresh();
    expect($fresh->face_descriptor)->toBeNull();
    expect((int) $fresh->failed_checkin_count)->toBe(0);
    expect((bool) $fresh->requires_face_reenrollment)->toBeTrue();
});
