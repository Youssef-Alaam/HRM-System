<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootAssetsTest();
});

it('lets Admin add a category', function () {
    $org = makeAssetsOrg();
    actingAsAssetsAdmin($org);

    test()->post('/admin/asset-categories', [
        'name' => 'Helmet',
        'icon_name' => 'hard-hat',
    ])->assertRedirect();

    expect(AssetCategory::query()->where('name', 'Helmet')->exists())->toBeTrue();
});

it('lets Admin edit a category', function () {
    $org = makeAssetsOrg();
    $cat = makeAssetCategory($org, 'Laptop');
    actingAsAssetsAdmin($org);

    test()->patch("/admin/asset-categories/{$cat->id}", [
        'description' => 'Now with new copy.',
    ])->assertRedirect();

    expect($cat->fresh()->description)->toBe('Now with new copy.');
});

it('lets Admin disable an empty category but blocks one with assets attached', function () {
    $org = makeAssetsOrg();
    actingAsAssetsAdmin($org);

    $emptyCat = makeAssetCategory($org, 'Empty');
    $usedCat = makeAssetCategory($org, 'Has assets');
    Asset::create([
        'org_id' => $org->id,
        'asset_category_id' => $usedCat->id,
        'name' => 'Whatever', 'value_piasters' => 1, 'condition_at_acquisition' => 'new',
    ]);

    test()->delete("/admin/asset-categories/{$emptyCat->id}")->assertRedirect();
    expect(AssetCategory::withTrashed()->find($emptyCat->id)->is_active)->toBeFalse();

    test()->delete("/admin/asset-categories/{$usedCat->id}")
        ->assertSessionHasErrors('category');
    expect(AssetCategory::withTrashed()->find($usedCat->id)->is_active)->toBeTrue();
});

it('blocks HR from managing categories', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);

    test()->get('/admin/asset-categories')->assertForbidden();
    test()->post('/admin/asset-categories', ['name' => 'Sneaky'])->assertForbidden();
});

it('rejects duplicate category names within the same org', function () {
    $org = makeAssetsOrg();
    makeAssetCategory($org, 'Laptop');
    actingAsAssetsAdmin($org);

    test()->post('/admin/asset-categories', ['name' => 'Laptop'])
        ->assertSessionHasErrors('name');
});
