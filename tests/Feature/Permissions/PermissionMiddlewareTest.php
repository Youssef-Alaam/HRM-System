<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Test routes — registered fresh per test so we don't pollute the app's route table.
        Route::middleware(['web', 'auth', 'role:'.RoleDefinitions::ROLE_ADMIN])
            ->get('/test/admin-only', fn () => 'admin-area');

        Route::middleware(['web', 'auth', 'permission:payroll.run'])
            ->get('/test/payroll-run', fn () => 'payroll-area');

        Route::middleware(['web', 'auth', 'permission:chat.send'])
            ->get('/test/chat-send', fn () => 'chat-area');
    }

    public function test_role_middleware_blocks_non_admin(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole(RoleDefinitions::ROLE_EMPLOYEE);

        $this->actingAs($employee)->get('/test/admin-only')->assertStatus(403);
    }

    public function test_role_middleware_lets_admin_through(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleDefinitions::ROLE_ADMIN);

        $this->actingAs($admin)->get('/test/admin-only')
            ->assertOk()
            ->assertSee('admin-area');
    }

    public function test_permission_middleware_blocks_user_without_permission(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(RoleDefinitions::ROLE_MANAGER);

        $this->actingAs($manager)->get('/test/payroll-run')->assertStatus(403);
    }

    public function test_permission_middleware_lets_hr_run_payroll(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole(RoleDefinitions::ROLE_HR);

        $this->actingAs($hr)->get('/test/payroll-run')
            ->assertOk()
            ->assertSee('payroll-area');
    }

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get('/test/admin-only')->assertRedirect('/login');
    }

    public function test_each_role_can_send_chat_by_default(): void
    {
        foreach (RoleDefinitions::roles() as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $this->actingAs($user)->get('/test/chat-send')
                ->assertOk()
                ->assertSee('chat-area', false);
        }
    }
}
