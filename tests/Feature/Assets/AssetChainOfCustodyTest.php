<?php

use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootAssetsTest();
});

it('reassigning to a new employee closes the previous open assignment and opens a new one', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);
    $alice = makeAssetEmployee($org);
    $bob = makeAssetEmployee($org);

    $asset = Asset::create([
        'org_id' => $org->id, 'asset_category_id' => $cat->id,
        'name' => 'Travelling laptop', 'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
    ]);

    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $alice->id,
        'condition_at_assignment' => 'new',
    ])->assertRedirect();

    // Reassign to Bob without an explicit return.
    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $bob->id,
        'condition_at_assignment' => 'used',
    ])->assertRedirect();

    $chain = AssetAssignment::query()
        ->where('asset_id', $asset->id)
        ->orderBy('id')
        ->get();

    expect($chain)->toHaveCount(2);
    expect($chain[0]->employee_id)->toBe($alice->id);
    expect($chain[0]->returned_at)->not->toBeNull(); // auto-closed
    expect($chain[1]->employee_id)->toBe($bob->id);
    expect($chain[1]->returned_at)->toBeNull(); // currently held

    $asset->refresh();
    expect($asset->current_employee_id)->toBe($bob->id);
});

it('mark-lost closes the open assignment with return_condition=lost', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);
    $alice = makeAssetEmployee($org);
    $asset = Asset::create([
        'org_id' => $org->id, 'asset_category_id' => $cat->id,
        'name' => 'Phone', 'value_piasters' => 1_500_000,
        'condition_at_acquisition' => 'new',
    ]);

    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $alice->id,
        'condition_at_assignment' => 'new',
    ])->assertRedirect();

    test()->post("/assets/{$asset->id}/mark-lost", [
        'notes' => 'Left in cab on Tuesday.',
    ])->assertRedirect();

    expect($asset->fresh()->current_status)->toBe(Asset::STATUS_LOST);
    $assignment = AssetAssignment::query()->latest('id')->first();
    expect($assignment->return_condition)->toBe('lost');
    expect($assignment->returned_at)->not->toBeNull();
});
