<?php

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootAssetsTest();
});

it('blocks the same serial number twice in the same org', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);

    test()->post('/assets', [
        'asset_category_id' => $cat->id,
        'name' => 'A',
        'serial_number' => 'XPS-9999',
        'value_piasters' => 1,
        'condition_at_acquisition' => 'new',
    ])->assertRedirect();

    test()->post('/assets', [
        'asset_category_id' => $cat->id,
        'name' => 'B',
        'serial_number' => 'XPS-9999',
        'value_piasters' => 1,
        'condition_at_acquisition' => 'new',
    ])->assertSessionHasErrors('serial_number');
});

it('allows the same serial number across two different orgs', function () {
    $orgA = makeAssetsOrg();
    $orgB = \App\Models\Organization::factory()->create();
    $catA = makeAssetCategory($orgA);
    $catB = makeAssetCategory($orgB);

    actingAsAssetsHr($orgA);
    test()->post('/assets', [
        'asset_category_id' => $catA->id,
        'name' => 'A',
        'serial_number' => 'XPS-9999',
        'value_piasters' => 1,
        'condition_at_acquisition' => 'new',
    ])->assertRedirect();

    actingAsAssetsHr($orgB);
    test()->post('/assets', [
        'asset_category_id' => $catB->id,
        'name' => 'B',
        'serial_number' => 'XPS-9999',
        'value_piasters' => 1,
        'condition_at_acquisition' => 'new',
    ])->assertRedirect();

    expect(Asset::query()->withoutGlobalScopes()->where('serial_number', 'XPS-9999')->count())->toBe(2);
});

it('allows multiple null-serial assets in the same org', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);

    foreach (range(1, 3) as $i) {
        test()->post('/assets', [
            'asset_category_id' => $cat->id,
            'name' => "Bundle {$i}",
            'value_piasters' => 100_000,
            'condition_at_acquisition' => 'new',
        ])->assertRedirect();
    }

    expect(Asset::count())->toBe(3);
});
