<?php

use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootAssetsTest();
});

it('returns an asset in good condition: closes assignment + asset back to in_pool', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);
    $employee = makeAssetEmployee($org);
    $asset = Asset::create([
        'org_id' => $org->id,
        'asset_category_id' => $cat->id,
        'name' => 'Loaner laptop',
        'value_piasters' => 3_000_000,
        'condition_at_acquisition' => 'new',
    ]);

    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $employee->id,
        'condition_at_assignment' => 'new',
    ])->assertRedirect();

    test()->post("/assets/{$asset->id}/return", [
        'return_condition' => 'good',
    ])->assertRedirect();

    $asset->refresh();
    expect($asset->current_status)->toBe(Asset::STATUS_IN_POOL);
    expect($asset->current_employee_id)->toBeNull();

    $assignment = AssetAssignment::query()->latest('id')->first();
    expect($assignment->returned_at)->not->toBeNull();
    expect($assignment->return_condition)->toBe('good');
});

it('requires return_notes when return_condition is damaged or lost', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);
    $employee = makeAssetEmployee($org);
    $asset = Asset::create([
        'org_id' => $org->id,
        'asset_category_id' => $cat->id,
        'name' => 'Phone',
        'value_piasters' => 1_500_000,
        'condition_at_acquisition' => 'new',
    ]);
    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $employee->id,
        'condition_at_assignment' => 'new',
    ])->assertRedirect();

    test()->post("/assets/{$asset->id}/return", [
        'return_condition' => 'damaged',
    ])->assertSessionHasErrors('return_notes');
});

it('damaged return mirrors the damaged status onto the asset', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);
    $employee = makeAssetEmployee($org);
    $asset = Asset::create([
        'org_id' => $org->id,
        'asset_category_id' => $cat->id,
        'name' => 'Phone',
        'value_piasters' => 1_500_000,
        'condition_at_acquisition' => 'new',
    ]);
    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $employee->id,
        'condition_at_assignment' => 'new',
    ])->assertRedirect();

    test()->post("/assets/{$asset->id}/return", [
        'return_condition' => 'damaged',
        'return_notes' => 'Cracked screen.',
    ])->assertRedirect();

    expect($asset->fresh()->current_status)->toBe(Asset::STATUS_DAMAGED);
});

it('blocks return on an asset that is not currently assigned', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);
    $asset = Asset::create([
        'org_id' => $org->id,
        'asset_category_id' => $cat->id,
        'name' => 'Idle laptop',
        'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
    ]);

    test()->post("/assets/{$asset->id}/return", [
        'return_condition' => 'good',
    ])->assertSessionHasErrors('return_condition');
});
