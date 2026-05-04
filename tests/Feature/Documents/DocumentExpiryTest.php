<?php

use App\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootDocumentsTest();
});

it('marks a document expired once the expiry_date is in the past', function () {
    $org = makeDocumentsOrg();
    $employee = makeEmployeeFor($org);

    $doc = EmployeeDocument::create([
        'org_id' => $org->id,
        'employee_id' => $employee->id,
        'file_path' => 'employee-documents/x.pdf',
        'original_filename' => 'x.pdf',
        'mime_type' => 'application/pdf',
        'file_size_bytes' => 1024,
        'expiry_date' => now()->subDay(),
        'uploaded_by_user_id' => actingAsHrIn($org)->id,
    ]);

    expect($doc->isExpired())->toBeTrue();
    expect($doc->isExpiringWithin(30))->toBeFalse();
});

it('flags a document expiring within 30 days', function () {
    $org = makeDocumentsOrg();
    $employee = makeEmployeeFor($org);

    $doc = EmployeeDocument::create([
        'org_id' => $org->id,
        'employee_id' => $employee->id,
        'file_path' => 'employee-documents/x.pdf',
        'original_filename' => 'x.pdf',
        'mime_type' => 'application/pdf',
        'file_size_bytes' => 1024,
        'expiry_date' => now()->addDays(20),
        'uploaded_by_user_id' => actingAsHrIn($org)->id,
    ]);

    expect($doc->isExpired())->toBeFalse();
    expect($doc->isExpiringWithin(30))->toBeTrue();
});

it('returns expiring + expired counts in the documents landing totals', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);
    $hr = \App\Models\User::query()->where('org_id', $org->id)->firstOrFail();

    EmployeeDocument::create([
        'org_id' => $org->id,
        'employee_id' => $employee->id,
        'file_path' => 'employee-documents/expired.pdf',
        'original_filename' => 'expired.pdf',
        'mime_type' => 'application/pdf',
        'file_size_bytes' => 1024,
        'expiry_date' => now()->subDays(5),
        'uploaded_by_user_id' => $hr->id,
    ]);
    EmployeeDocument::create([
        'org_id' => $org->id,
        'employee_id' => $employee->id,
        'file_path' => 'employee-documents/soon.pdf',
        'original_filename' => 'soon.pdf',
        'mime_type' => 'application/pdf',
        'file_size_bytes' => 1024,
        'expiry_date' => now()->addDays(10),
        'uploaded_by_user_id' => $hr->id,
    ]);

    test()->get('/documents')
        ->assertInertia(fn ($p) => $p
            ->component('Documents/Index')
            ->where('totals.expired', 1)
            ->where('totals.expiring_30d', 1));
});
