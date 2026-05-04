<?php

use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootDocumentsTest();
});

it('seeds 7 required types for Egyptian employees', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    $employee = makeEmployeeFor($org, ['is_expat' => false, 'gender' => 'female']);

    $applicableRequired = DocumentType::query()
        ->where('org_id', $org->id)
        ->where('is_required', true)
        ->where('is_active', true)
        ->get()
        ->filter(fn (DocumentType $t) => $t->appliesTo($employee));

    expect($applicableRequired)->toHaveCount(7);
});

it('seeds 6 required types for expat employees', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);
    $employee = makeEmployeeFor($org, ['is_expat' => true, 'nationality' => 'British', 'gender' => 'male']);

    $applicableRequired = DocumentType::query()
        ->where('org_id', $org->id)
        ->where('is_required', true)
        ->where('is_active', true)
        ->get()
        ->filter(fn (DocumentType $t) => $t->appliesTo($employee));

    // 3 universal (Birth, Degree, Employment record) + 3 expat-specific
    expect($applicableRequired)->toHaveCount(6);
    expect($applicableRequired->pluck('name'))->toContain('Passport copy', 'Work permit', 'Residency permit');
});

it('seeds 5 optional types reachable to anyone', function () {
    $org = makeDocumentsOrg();
    seedDocumentTypesFor($org);

    $optional = DocumentType::query()
        ->where('org_id', $org->id)
        ->where('is_required', false)
        ->where('is_active', true)
        ->get();

    expect($optional)->toHaveCount(5);
});

it('respects egyptian_male_only applies_to gating', function () {
    $org = makeDocumentsOrg();
    DocumentType::create([
        'org_id' => $org->id,
        'name' => 'Military service status',
        'applies_to' => DocumentType::APPLIES_EGYPTIAN_MALE,
        'is_required' => true,
        'order_index' => 0,
        'is_active' => true,
    ]);

    $male = makeEmployeeFor($org, ['gender' => 'male', 'is_expat' => false]);
    $female = makeEmployeeFor($org, ['gender' => 'female', 'is_expat' => false]);
    $expat = makeEmployeeFor($org, ['gender' => 'male', 'is_expat' => true]);

    $type = DocumentType::query()->where('name', 'Military service status')->first();

    expect($type->appliesTo($male))->toBeTrue();
    expect($type->appliesTo($female))->toBeFalse();
    expect($type->appliesTo($expat))->toBeFalse();
});
