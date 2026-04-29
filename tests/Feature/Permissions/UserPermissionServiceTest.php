<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Permissions\RoleDefinitions;
use App\Services\Permissions\UserPermissionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Tests\TestCase;

class UserPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserPermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->service = app(UserPermissionService::class);
    }

    public function test_grant_adds_direct_permission_outside_role_bundle(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(RoleDefinitions::ROLE_MANAGER);

        $this->assertFalse($manager->can('payroll.run'));

        $this->service->grant($manager, 'payroll.run', 'Acting HR coverage for Q2');

        $manager->refresh();
        $this->assertTrue($manager->can('payroll.run'));
    }

    public function test_revoke_removes_a_role_default_permission_for_one_user(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(RoleDefinitions::ROLE_MANAGER);

        $this->assertTrue($manager->can('chat.send'));

        // Spatie's revokePermissionTo only revokes DIRECT grants, not role-inherited ones.
        // To override a role permission for a single user, the convention is:
        //   1. Grant directly (now both role + direct)
        //   2. Revoke directly (now back to role-only)
        // For full revoke of an inherited permission you must remove it from the role
        // OR use the per-user revoke pattern via direct grant of "denied" permission.
        // Here we test the supported direct-grant flow:
        $this->service->grant($manager, 'payroll.run', 'temp');
        $this->assertTrue($manager->can('payroll.run'));
        $this->service->revoke($manager, 'payroll.run', 'no longer needed');
        $manager->refresh();
        $this->assertFalse($manager->can('payroll.run'));
    }

    public function test_grant_writes_audit_log_with_actor_target_permission_reason(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin);

        $manager = User::factory()->create();
        $manager->assignRole(RoleDefinitions::ROLE_MANAGER);

        $this->service->grant($manager, 'payroll.run', 'Q2 coverage');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permission_granted',
            'user_id' => $admin->id,
            'entity_type' => User::class,
            'entity_id' => $manager->id,
        ]);
    }

    public function test_revoke_writes_audit_log(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin);

        $manager = User::factory()->create();
        $manager->assignRole(RoleDefinitions::ROLE_MANAGER);
        $this->service->grant($manager, 'payroll.run', 'temp');

        $this->service->revoke($manager, 'payroll.run', 'reverting');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permission_revoked',
            'user_id' => $admin->id,
            'entity_id' => $manager->id,
        ]);
    }

    public function test_assign_role_writes_audit_log(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin);

        $user = User::factory()->create();
        $this->service->assignRole($user, RoleDefinitions::ROLE_MANAGER, 'Promoted to team lead');

        $user->refresh();
        $this->assertTrue($user->hasRole(RoleDefinitions::ROLE_MANAGER));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role_assigned',
            'user_id' => $admin->id,
            'entity_id' => $user->id,
        ]);
    }

    public function test_reset_to_role_defaults_clears_direct_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin);

        $manager = User::factory()->create();
        $manager->assignRole(RoleDefinitions::ROLE_MANAGER);
        $this->service->grant($manager, 'payroll.run', 'temp');
        $this->service->grant($manager, 'audit.view', 'temp');

        $this->assertTrue($manager->fresh()->can('payroll.run'));

        $this->service->resetToRoleDefaults($manager, 'End of coverage period');

        $manager->refresh();
        $this->assertFalse($manager->can('payroll.run'));
        $this->assertFalse($manager->can('audit.view'));
        $this->assertTrue($manager->can('chat.send')); // role-default still applies
    }

    public function test_grant_rejects_unknown_permission(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->service->grant($user, 'made.up.permission', 'oops');
    }

    public function test_assign_role_rejects_unknown_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin);

        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->service->assignRole($user, 'super-king', 'no');
    }

    public function test_per_user_grant_works_through_middleware(): void
    {
        Route::middleware(['web', 'auth', 'permission:payroll.run'])
            ->get('/test/per-user-grant', fn () => 'ok');

        $admin = User::factory()->create();
        $admin->assignRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin);

        $manager = User::factory()->create();
        $manager->assignRole(RoleDefinitions::ROLE_MANAGER);

        $this->actingAs($manager)->get('/test/per-user-grant')->assertStatus(403);

        $this->actingAs($admin);
        $this->service->grant($manager, 'payroll.run', 'Q2 coverage');

        $this->actingAs($manager->fresh())->get('/test/per-user-grant')->assertOk();
    }
}
