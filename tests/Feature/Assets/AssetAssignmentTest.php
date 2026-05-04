<?php

use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootAssetsTest();
});

it('assigns an asset to an employee — creates a row, sets current_employee_id, and auto-derives age from acquired_date', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);
    $employee = makeAssetEmployee($org);

    $asset = Asset::create([
        'org_id' => $org->id,
        'asset_category_id' => $cat->id,
        'name' => 'Dell XPS',
        'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
        // Acquired exactly 12 months ago so the snapshot lands at 12.
        'acquired_date' => now()->subMonths(12)->startOfDay()->toDateString(),
    ]);

    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $employee->id,
        'condition_at_assignment' => 'used',
    ])->assertRedirect();

    $asset->refresh();
    expect($asset->current_status)->toBe(Asset::STATUS_ASSIGNED);
    expect($asset->current_employee_id)->toBe($employee->id);

    $assignment = AssetAssignment::query()->firstOrFail();
    expect($assignment->employee_id)->toBe($employee->id);
    expect($assignment->age_at_assignment_months)->toBe(12);
    expect($assignment->condition_at_assignment)->toBe('used');
    expect($assignment->returned_at)->toBeNull();
});

it('blocks employees and managers from assigning', function () {
    $org = makeAssetsOrg();
    $cat = makeAssetCategory($org);
    $employee = makeAssetEmployee($org);
    $asset = Asset::create([
        'org_id' => $org->id,
        'asset_category_id' => $cat->id,
        'name' => 'Phone',
        'value_piasters' => 1_000_000,
        'condition_at_acquisition' => 'new',
    ]);

    actingAsAssetsEmployee($org);
    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $employee->id,
        'condition_at_assignment' => 'new',
    ])->assertForbidden();

    actingAsAssetsManager($org);
    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $employee->id,
        'condition_at_assignment' => 'new',
    ])->assertForbidden();
});

it('blocks assignment when status is lost / damaged / written_off', function () {
    $org = makeAssetsOrg();
    actingAsAssetsHr($org);
    $cat = makeAssetCategory($org);
    $employee = makeAssetEmployee($org);

    $asset = Asset::create([
        'org_id' => $org->id,
        'asset_category_id' => $cat->id,
        'name' => 'Lost laptop',
        'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
        'current_status' => Asset::STATUS_LOST,
    ]);

    test()->post("/assets/{$asset->id}/assign", [
        'employee_id' => $employee->id,
        'condition_at_assignment' => 'new',
    ])->assertSessionHasErrors('employee_id');
});
