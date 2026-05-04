<?php

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootAssetsTest();
});

it('lets HR create an asset that lands in_pool', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org, 'Laptop');

    test()->post('/assets', [
        'asset_category_id' => $cat->id,
        'name' => 'Dell XPS 15',
        'serial_number' => 'XPS-9999',
        'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
    ])->assertRedirect();

    $asset = Asset::query()->firstOrFail();
    expect($asset->name)->toBe('Dell XPS 15')
        ->and($asset->current_status)->toBe(Asset::STATUS_IN_POOL)
        ->and($asset->serial_number)->toBe('XPS-9999')
        ->and((int) $asset->value_piasters)->toBe(4_000_000);
});

it('blocks employees and managers from creating', function () {
    $org = makeAssetsOrg();
    $cat = makeAssetCategory($org);

    actingAsAssetsEmployee($org);
    test()->post('/assets', [
        'asset_category_id' => $cat->id,
        'name' => 'Sneaky',
        'value_piasters' => 1,
        'condition_at_acquisition' => 'new',
    ])->assertForbidden();

    actingAsAssetsManager($org);
    test()->post('/assets', [
        'asset_category_id' => $cat->id,
        'name' => 'Sneaky',
        'value_piasters' => 1,
        'condition_at_acquisition' => 'new',
    ])->assertForbidden();
});

it('lets Admin create assets too', function () {
    $org = makeAssetsOrg();
    actingAsAssetsAdmin($org);
    $cat = makeAssetCategory($org);

    test()->post('/assets', [
        'asset_category_id' => $cat->id,
        'name' => 'MacBook Pro',
        'value_piasters' => 8_000_000,
        'condition_at_acquisition' => 'new',
    ])->assertRedirect();

    expect(Asset::count())->toBe(1);
});
