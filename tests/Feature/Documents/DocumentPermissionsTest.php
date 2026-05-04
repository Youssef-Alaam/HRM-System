<?php

use App\Models\EmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootDocumentsTest();
});

it('blocks employees from listing documents on the sidebar landing', function () {
    $org = makeDocumentsOrg();
    actingAsEmployeeIn($org);

    test()->get('/documents')->assertForbidden();
});

it('blocks managers from listing documents', function () {
    $org = makeDocumentsOrg();
    $user = \App\Models\User::factory()->create(['org_id' => $org->id]);
    $user->assignRole(\App\Permissions\RoleDefinitions::ROLE_MANAGER);
    test()->actingAs($user);

    test()->get('/documents')->assertForbidden();
});

it('lets HR access the documents landing', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);

    test()->get('/documents')->assertOk();
});

it('blocks employees from uploading even for themselves', function () {
    $org = makeDocumentsOrg();
    $employee = makeEmployeeFor($org);
    actingAsEmployeeIn($org, $employee);

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile(),
    ])->assertForbidden();
});

it('blocks employees from downloading their own documents', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile(),
    ])->assertRedirect();

    $doc = EmployeeDocument::query()->firstOrFail();

    actingAsEmployeeIn($org, $employee);
    test()->get("/employees/{$employee->id}/documents/{$doc->id}/download")
        ->assertForbidden();
});

it('lets HR download any document in their org', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile('id.pdf'),
    ])->assertRedirect();

    $doc = EmployeeDocument::query()->firstOrFail();
    test()->get("/employees/{$employee->id}/documents/{$doc->id}/download")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('blocks deletion by anyone without documents.delete', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);
    test()->post("/employees/{$employee->id}/documents", ['file' => pdfUploadedFile()])
        ->assertRedirect();

    $doc = EmployeeDocument::query()->firstOrFail();

    actingAsEmployeeIn($org, $employee);
    test()->delete("/employees/{$employee->id}/documents/{$doc->id}")
        ->assertForbidden();

    expect(EmployeeDocument::withTrashed()->find($doc->id)->deleted_at)->toBeNull();
});
