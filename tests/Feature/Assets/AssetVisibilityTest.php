<?php

use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    bootAssetsTest();
});

it('employee mode: sees only their own currently-assigned assets', function () {
    $org = makeAssetsOrg();
    $cat = makeAssetCategory($org);

    $alice = makeAssetEmployee($org);
    $bob = makeAssetEmployee($org);

    $aliceLaptop = Asset::create([
        'org_id' => $org->id, 'asset_category_id' => $cat->id,
        'name' => 'Alice laptop', 'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
        'current_status' => Asset::STATUS_ASSIGNED,
        'current_employee_id' => $alice->id,
    ]);
    AssetAssignment::create([
        'org_id' => $org->id, 'asset_id' => $aliceLaptop->id,
        'employee_id' => $alice->id, 'assigned_at' => now(),
        'age_at_assignment_months' => 0, 'condition_at_assignment' => 'new',
        'assigned_by_user_id' => actingAsAssetsHr($org)->id,
    ]);

    Asset::create([
        'org_id' => $org->id, 'asset_category_id' => $cat->id,
        'name' => 'Bob laptop', 'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
        'current_status' => Asset::STATUS_ASSIGNED,
        'current_employee_id' => $bob->id,
    ]);

    actingAsAssetsEmployee($org, $alice);

    test()->get('/assets')
        ->assertInertia(fn ($p) => $p
            ->component('Assets/Index')
            ->where('mode', 'self')
            ->has('own', 1)
            ->where('own.0.name', 'Alice laptop'));
});

it('manager mode: sees only own current — NOT their teams', function () {
    $org = makeAssetsOrg();
    $cat = makeAssetCategory($org);

    // Manager linked to an employee record, plus a direct report.
    $managerEmp = makeAssetEmployee($org);
    $report = makeAssetEmployee($org, ['manager_id' => $managerEmp->id]);

    Asset::create([
        'org_id' => $org->id, 'asset_category_id' => $cat->id,
        'name' => 'Manager laptop', 'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
        'current_status' => Asset::STATUS_ASSIGNED,
        'current_employee_id' => $managerEmp->id,
    ]);
    Asset::create([
        'org_id' => $org->id, 'asset_category_id' => $cat->id,
        'name' => 'Report laptop', 'value_piasters' => 4_000_000,
        'condition_at_acquisition' => 'new',
        'current_status' => Asset::STATUS_ASSIGNED,
        'current_employee_id' => $report->id,
    ]);

    actingAsAssetsManager($org, $managerEmp);

    test()->get('/assets')
        ->assertInertia(fn ($p) => $p
            ->component('Assets/Index')
            ->where('mode', 'self')
            ->has('own', 1)
            ->where('own.0.name', 'Manager laptop'));
});

it('hr mode: sees the org-wide list', function () {
    $org = makeAssetsOrg();
    $cat = makeAssetCategory($org);
    actingAsAssetsHr($org);

    foreach (range(1, 4) as $i) {
        Asset::create([
            'org_id' => $org->id, 'asset_category_id' => $cat->id,
            'name' => "Asset {$i}", 'value_piasters' => 1_000_000,
            'condition_at_acquisition' => 'new',
        ]);
    }

    test()->get('/assets')
        ->assertInertia(fn ($p) => $p
            ->component('Assets/Index')
            ->where('mode', 'org')
            ->has('assets.data', 4));
});

it('employee with no assets gets an empty roster, not an error', function () {
    $org = makeAssetsOrg();
    $employee = makeAssetEmployee($org);
    actingAsAssetsEmployee($org, $employee);

    test()->get('/assets')
        ->assertInertia(fn ($p) => $p
            ->where('mode', 'self')
            ->has('own', 0));
});
