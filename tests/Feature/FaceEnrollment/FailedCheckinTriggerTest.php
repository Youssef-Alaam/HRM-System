<?php

use App\Services\FaceEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootFaceTest();
});

it('flips requires_face_reenrollment after the threshold of consecutive failed check-ins', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    $service = app(FaceEnrollmentService::class);
    $threshold = FaceEnrollmentService::FAILED_CHECKIN_THRESHOLD;

    for ($i = 1; $i < $threshold; $i++) {
        $service->recordFailedCheckin($employee);
        $employee->refresh();
        expect((bool) $employee->requires_face_reenrollment)->toBeFalse();
        expect((int) $employee->failed_checkin_count)->toBe($i);
    }

    // The threshold-th failure trips the flag.
    $service->recordFailedCheckin($employee);
    $employee->refresh();
    expect((int) $employee->failed_checkin_count)->toBe($threshold);
    expect((bool) $employee->requires_face_reenrollment)->toBeTrue();
});

it('successful check-in clears the failed counter', function () {
    $org = makeFaceOrg();
    actingAsFaceHr($org);
    $employee = makeFaceEmployee($org);

    $service = app(FaceEnrollmentService::class);
    $service->recordFailedCheckin($employee);
    $service->recordFailedCheckin($employee);
    $employee->refresh();
    expect((int) $employee->failed_checkin_count)->toBe(2);

    $service->recordSuccessfulCheckin($employee);
    $employee->refresh();
    expect((int) $employee->failed_checkin_count)->toBe(0);
});
