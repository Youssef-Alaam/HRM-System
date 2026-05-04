<?php

use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootDocumentsTest();
});

it('lets Admin add a new document type', function () {
    $org = makeDocumentsOrg();
    actingAsAdminIn($org);

    test()->post('/admin/document-types', [
        'name' => 'Driving abstract',
        'applies_to' => DocumentType::APPLIES_ALL,
        'is_required' => false,
    ])->assertRedirect();

    expect(DocumentType::query()->where('name', 'Driving abstract')->exists())->toBeTrue();
});

it('lets Admin edit a document type', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsAdminIn($org);
    $type = findDocumentType('Driving license');

    test()->patch("/admin/document-types/{$type->id}", [
        'is_required' => true,
        'default_expiry_months' => 60,
    ])->assertRedirect();

    $fresh = $type->fresh();
    expect($fresh->is_required)->toBeTrue();
    expect($fresh->default_expiry_months)->toBe(60);
});

it('lets Admin disable a document type', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsAdminIn($org);
    $type = findDocumentType('Performance review');

    test()->delete("/admin/document-types/{$type->id}")->assertRedirect();

    $fresh = DocumentType::withTrashed()->find($type->id);
    expect($fresh->is_active)->toBeFalse();
    expect($fresh->deleted_at)->not->toBeNull();
});

it('blocks HR from managing document types', function () {
    $org = makeDocumentsOrg();
    actingAsHrIn($org);

    test()->get('/admin/document-types')->assertForbidden();
    test()->post('/admin/document-types', [
        'name' => 'Sneaky',
        'applies_to' => 'all',
        'is_required' => false,
    ])->assertForbidden();
});

it('blocks employees and managers from managing document types', function () {
    $org = makeDocumentsOrg();

    actingAsEmployeeIn($org);
    test()->get('/admin/document-types')->assertForbidden();

    $manager = \App\Models\User::factory()->create(['org_id' => $org->id]);
    $manager->assignRole(\App\Permissions\RoleDefinitions::ROLE_MANAGER);
    test()->actingAs($manager);
    test()->get('/admin/document-types')->assertForbidden();
});

it('rejects duplicate names within the same org', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    actingAsAdminIn($org);

    test()->post('/admin/document-types', [
        'name' => 'Driving license', // already seeded
        'applies_to' => 'all',
        'is_required' => false,
    ])->assertSessionHasErrors('name');
});
