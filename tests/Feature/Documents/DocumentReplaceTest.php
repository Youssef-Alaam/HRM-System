<?php

use App\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootDocumentsTest();
});

it('replacing the same (employee, type) soft-deletes the old row and creates a new one', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);
    $type = findDocumentType('National ID copy');

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile('first.pdf'),
        'document_type_id' => $type->id,
    ])->assertRedirect();

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile('renewed.pdf'),
        'document_type_id' => $type->id,
    ])->assertRedirect();

    $active = EmployeeDocument::query()
        ->where('employee_id', $employee->id)
        ->where('document_type_id', $type->id)
        ->get();
    expect($active)->toHaveCount(1);
    expect($active->first()->original_filename)->toBe('renewed.pdf');

    $all = EmployeeDocument::withTrashed()
        ->where('employee_id', $employee->id)
        ->where('document_type_id', $type->id)
        ->get();
    expect($all)->toHaveCount(2);
    expect($all->whereNotNull('deleted_at'))->toHaveCount(1);
});

it('lets HR update metadata (issued / expiry / notes) without replacing the file', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile(),
    ])->assertRedirect();

    $doc = EmployeeDocument::query()->firstOrFail();
    $originalPath = $doc->file_path;

    test()->patch("/employees/{$employee->id}/documents/{$doc->id}", [
        'expiry_date' => now()->addYear()->toDateString(),
        'notes' => 'On file under HR-2026 cabinet.',
    ])->assertRedirect();

    $fresh = $doc->fresh();
    expect($fresh->file_path)->toBe($originalPath);
    expect($fresh->notes)->toBe('On file under HR-2026 cabinet.');
});
