<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Apply Tests\TestCase to every file under tests/Feature and tests/Unit.
| Existing PHPUnit-style class tests in those directories continue to run
| unchanged — Pest builds on top of PHPUnit. New tests should use Pest's
| function syntax (test(), it(), describe()).
|
| RefreshDatabase is opt-in per file: `uses(RefreshDatabase::class);` at
| the top of any test file that needs a clean DB. Don't auto-apply it
| globally — some smoke / route-existence tests don't need it.
*/

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a user in an org with the given role and act as them.
 * Requires RolePermissionSeeder to have run in the test's setUp.
 */
function actingAsRole(string $role, ?\App\Models\Organization $org = null): \App\Models\User
{
    $org ??= \App\Models\Organization::factory()->create();
    $user = \App\Models\User::factory()->create(['org_id' => $org->id]);
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}
