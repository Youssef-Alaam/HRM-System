<?php

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootAssetsTest();
});

it('OrgScope hides assets belonging to another org', function () {
    $orgA = makeAssetsOrg();
    $orgB = \App\Models\Organization::factory()->create(['name' => 'OtherCorp']);

    $catA = makeAssetCategory($orgA, 'Laptop');
    $catB = makeAssetCategory($orgB, 'Laptop');

    Asset::create([
        'org_id' => $orgA->id, 'asset_category_id' => $catA->id,
        'name' => 'A asset', 'value_piasters' => 1, 'condition_at_acquisition' => 'new',
    ]);
    Asset::create([
        'org_id' => $orgB->id, 'asset_category_id' => $catB->id,
        'name' => 'B asset', 'value_piasters' => 1, 'condition_at_acquisition' => 'new',
    ]);

    actingAsAssetsHr($orgA);

    test()->get('/assets')
        ->assertInertia(fn ($p) => $p
            ->component('Assets/Index')
            ->has('assets.data', 1)
            ->where('assets.data.0.name', 'A asset'));
});

it('show route returns 404 for an asset in another org (OrgScope filters findOrFail)', function () {
    $orgA = makeAssetsOrg();
    $orgB = \App\Models\Organization::factory()->create();
    $catB = makeAssetCategory($orgB);

    $foreignAsset = Asset::create([
        'org_id' => $orgB->id, 'asset_category_id' => $catB->id,
        'name' => 'Off-tenant',
        'value_piasters' => 1,
        'condition_at_acquisition' => 'new',
    ]);

    actingAsAssetsHr($orgA);

    test()->get("/assets/{$foreignAsset->id}")->assertNotFound();
});
