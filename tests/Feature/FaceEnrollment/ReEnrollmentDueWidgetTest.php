<?php

use App\Models\Employee;
use App\Permissions\RoleDefinitions;
use App\Services\FaceEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootFaceTest();
});

it('exposes face_enrollment_status counts on the HR dashboard widget', function () {
    $org = makeFaceOrg();

    // Active descriptor, recent → no flag
    makeFaceEmployee($org, [
        'face_descriptor' => array_fill(0, 128, 0.1),
        'last_face_enrollment_at' => now()->subMonths(2),
    ]);
    // Due this month — 11 months ago
    makeFaceEmployee($org, [
        'face_descriptor' => array_fill(0, 128, 0.1),
        'last_face_enrollment_at' => now()->subMonths(11)->subDays(15),
    ]);
    // Overdue — 13 months ago
    makeFaceEmployee($org, [
        'face_descriptor' => array_fill(0, 128, 0.1),
        'last_face_enrollment_at' => now()->subMonths(13),
    ]);
    // Never enrolled
    makeFaceEmployee($org);
    // Forced re-enrollment
    makeFaceEmployee($org, [
        'face_descriptor' => array_fill(0, 128, 0.1),
        'last_face_enrollment_at' => now()->subMonths(1),
        'requires_face_reenrollment' => true,
    ]);

    $hr = actingAsFaceHr($org);

    test()->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('widgets.face_enrollment_status.due_this_month', 1)
            ->where('widgets.face_enrollment_status.overdue', 1)
            ->where('widgets.face_enrollment_status.never_enrolled', 1)
            ->where('widgets.face_enrollment_status.forced_reenrollment', 1));
});

it('hides the face widget for employees who lack face.view.any', function () {
    $org = makeFaceOrg();
    $employee = makeFaceEmployee($org);
    actingAsFaceEmployee($org, $employee);

    test()->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('widgets.face_enrollment_status'));
});
