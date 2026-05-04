<?php

use App\Models\EmployeeDocument;
use App\Services\EmployeeDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootDocumentsTest();
});

it('lets HR upload a PDF and stores it on the local disk', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);
    $type = findDocumentType('National ID copy');

    $file = pdfUploadedFile('national-id.pdf', 200);

    test()->post("/employees/{$employee->id}/documents", [
        'file' => $file,
        'document_type_id' => $type->id,
    ])->assertRedirect();

    $doc = EmployeeDocument::query()->firstOrFail();
    expect($doc->mime_type)->toBe('application/pdf')
        ->and($doc->original_filename)->toBe('national-id.pdf')
        ->and($doc->document_type_id)->toBe($type->id);

    Storage::disk(EmployeeDocumentService::STORAGE_DISK)->assertExists($doc->file_path);
});

it('accepts JPEG, PNG, and WebP image uploads', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    foreach (['png', 'jpg', 'webp'] as $ext) {
        $file = UploadedFile::fake()->image("doc.{$ext}", 600, 600);
        test()->post("/employees/{$employee->id}/documents", [
            'file' => $file,
        ])->assertRedirect();
    }

    expect(EmployeeDocument::count())->toBe(3);
});

it('rejects files larger than 10 MB', function () {
    $org = makeDocumentsOrg();
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    $tooBig = UploadedFile::fake()->create('big.pdf', 11_000, 'application/pdf');

    test()->post("/employees/{$employee->id}/documents", ['file' => $tooBig])
        ->assertSessionHasErrors('file');

    expect(EmployeeDocument::count())->toBe(0);
});

it('rejects MIME types outside the allow list', function () {
    $org = makeDocumentsOrg();
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);

    $exe = UploadedFile::fake()->create('payload.exe', 100, 'application/octet-stream');

    test()->post("/employees/{$employee->id}/documents", ['file' => $exe])
        ->assertSessionHasErrors('file');
});

it('auto-fills expiry_date from the document types default_expiry_months when blank', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsHrIn($org);
    $employee = makeEmployeeFor($org);
    $type = findDocumentType('Work permit'); // 12 month default

    test()->post("/employees/{$employee->id}/documents", [
        'file' => pdfUploadedFile(),
        'document_type_id' => $type->id,
    ])->assertRedirect();

    $doc = EmployeeDocument::query()->firstOrFail();
    expect($doc->expiry_date)->not->toBeNull();
    expect($doc->expiry_date->format('Y-m'))
        ->toBe(now()->addMonths(12)->format('Y-m'));
});
