<?php

use App\Models\EmployeeDocument;
use App\Services\EmployeeDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootDocumentsTest();
});

it('blocks upload number 31 with a clear message', function () {
    $org = makeDocumentsOrg();
    $hr = actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    // Pre-seed exactly the cap so the next upload would push past it.
    foreach (range(1, EmployeeDocumentService::PER_EMPLOYEE_CAP) as $i) {
        EmployeeDocument::create([
            'org_id' => $org->id,
            'employee_id' => $employee->id,
            'file_path' => "employee-documents/{$employee->id}/seed-{$i}.pdf",
            'original_filename' => "seed-{$i}.pdf",
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'uploaded_by_user_id' => $hr->id,
        ]);
    }

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile('over-the-cap.pdf'),
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('file');

    expect(EmployeeDocument::count())->toBe(EmployeeDocumentService::PER_EMPLOYEE_CAP);
});

it('does not count soft-deleted documents toward the cap', function () {
    $org = makeDocumentsOrg();
    $hr = actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    // 30 active + 1 soft-deleted = 30 active total, upload should pass.
    foreach (range(1, EmployeeDocumentService::PER_EMPLOYEE_CAP - 1) as $i) {
        EmployeeDocument::create([
            'org_id' => $org->id,
            'employee_id' => $employee->id,
            'file_path' => "employee-documents/{$employee->id}/seed-{$i}.pdf",
            'original_filename' => "seed-{$i}.pdf",
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'uploaded_by_user_id' => $hr->id,
        ]);
    }
    $tombstone = EmployeeDocument::create([
        'org_id' => $org->id,
        'employee_id' => $employee->id,
        'file_path' => "employee-documents/{$employee->id}/old.pdf",
        'original_filename' => 'old.pdf',
        'mime_type' => 'application/pdf',
        'file_size_bytes' => 1024,
        'uploaded_by_user_id' => $hr->id,
    ]);
    $tombstone->delete();

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile('thirtieth.pdf'),
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect(EmployeeDocument::count())->toBe(EmployeeDocumentService::PER_EMPLOYEE_CAP);
    expect(EmployeeDocument::withTrashed()->count())->toBe(EmployeeDocumentService::PER_EMPLOYEE_CAP + 1);
});
