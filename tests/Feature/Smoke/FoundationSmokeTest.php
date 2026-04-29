<?php

use App\Models\Holiday;
use App\Models\Organization;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Foundation smoke test (F10 — first Pest-style test)
|--------------------------------------------------------------------------
| Re-asserts the three foundation pillars from F10's done-when checklist
| in Pest idiom, as the canonical reference for new test files. Existing
| tests in tests/Feature/{Auth,Permissions,Tenancy} cover each pillar in
| depth using PHPUnit class style — both styles run side-by-side.
*/

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

describe('auth', function () {
    it('lets a user log in with valid credentials', function () {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    });

    it('rejects invalid credentials', function () {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    });
});

describe('rbac', function () {
    it('blocks an employee from a permission-gated admin route', function () {
        actingAsRole(RoleDefinitions::ROLE_EMPLOYEE);

        $this->get('/admin/holidays')->assertStatus(403);
    });

    it('lets an HR user through the same route', function () {
        actingAsRole(RoleDefinitions::ROLE_HR);

        $this->getJson('/admin/holidays')->assertOk();
    });
});

describe('multi-tenancy', function () {
    it('isolates records between orgs via OrgScope', function () {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $hrA = User::factory()->create(['org_id' => $orgA->id]);
        $hrB = User::factory()->create(['org_id' => $orgB->id]);

        $this->actingAs($hrA);
        Holiday::create(['date' => '2026-01-07', 'name' => 'Org-A Christmas']);

        $this->actingAs($hrB);
        Holiday::create(['date' => '2026-01-07', 'name' => 'Org-B Christmas']);

        $this->actingAs($hrA);
        expect(Holiday::count())->toBe(1)
            ->and(Holiday::first()->name)->toBe('Org-A Christmas');

        $this->actingAs($hrB);
        expect(Holiday::count())->toBe(1)
            ->and(Holiday::first()->name)->toBe('Org-B Christmas');
    });
});
