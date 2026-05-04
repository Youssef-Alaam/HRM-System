<?php

use App\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootDocumentsTest();
});

it('soft-delete removes the doc from the active list but keeps the row', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile('keep.pdf'),
    ])->assertRedirect();

    $doc = EmployeeDocument::query()->firstOrFail();

    test()->delete("/employees/{$employee->id}/documents/{$doc->id}")
        ->assertRedirect();

    expect(EmployeeDocument::count())->toBe(0);
    expect(EmployeeDocument::withTrashed()->find($doc->id)->deleted_at)->not->toBeNull();
});

it('after deleting a required doc, the matrix flags it as missing again', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org, ['is_expat' => false, 'gender' => 'female']);
    $type = findDocumentType('National ID copy');

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile(),
        'document_type_id' => $type->id,
    ])->assertRedirect();

    $beforeDelete = $employee->refresh();
    expect(EmployeeDocument::where('employee_id', $beforeDelete->id)->where('document_type_id', $type->id)->exists())
        ->toBeTrue();

    $doc = EmployeeDocument::query()->firstOrFail();
    test()->delete("/employees/{$employee->id}/documents/{$doc->id}")
        ->assertRedirect();

    expect(EmployeeDocument::where('employee_id', $employee->id)->where('document_type_id', $type->id)->exists())
        ->toBeFalse();
});

it('cannot delete a document attached to another employee — 404', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $alice = makeEmployeeFor($org);
    $bob = makeEmployeeFor($org);

    test()->post("/employees/{$alice->id}/documents", [
        'file' => pdfUploadedFile(),
    ])->assertRedirect();

    $alicesDoc = EmployeeDocument::query()->firstOrFail();

    test()->delete("/employees/{$bob->id}/documents/{$alicesDoc->id}")
        ->assertNotFound();
});
