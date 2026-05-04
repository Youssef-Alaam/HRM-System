<?php

namespace Tests\Feature\Layout;

use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceholderRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $org = \App\Models\Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_dashboard_renders_for_any_authenticated_user(): void
    {
        $user = $this->userWithRole(RoleDefinitions::ROLE_EMPLOYEE);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_dashboard_redirects_unauthenticated_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_employee_list_open_for_team_and_above(): void
    {
        // Updated 2026-04-30 with Feature 2: /employees list now requires
        // `employees.view.team` or `employees.view.any`. Employees see only
        // their own profile via /employees/{id} (covered in Feature 2 tests).
        foreach ([
            RoleDefinitions::ROLE_MANAGER,
            RoleDefinitions::ROLE_HR,
            RoleDefinitions::ROLE_ADMIN,
        ] as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get('/employees')->assertOk();
        }

        $employee = $this->userWithRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($employee)->get('/employees')->assertStatus(403);
    }

    public function test_payroll_route_blocks_employee_and_manager(): void
    {
        $employee = $this->userWithRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($employee)->get('/payroll')->assertStatus(403);

        $manager = $this->userWithRole(RoleDefinitions::ROLE_MANAGER);
        $this->actingAs($manager)->get('/payroll')->assertStatus(403);
    }

    public function test_payroll_route_open_for_hr_and_admin(): void
    {
        foreach ([RoleDefinitions::ROLE_HR, RoleDefinitions::ROLE_ADMIN] as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get('/payroll')->assertOk();
        }
    }

    public function test_audit_log_route_only_open_for_admin(): void
    {
        $hr = $this->userWithRole(RoleDefinitions::ROLE_HR);
        $this->actingAs($hr)->get('/admin/audit-log')->assertStatus(403);

        $admin = $this->userWithRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin)->get('/admin/audit-log')->assertOk();
    }

    public function test_settings_route_only_open_for_admin(): void
    {
        $hr = $this->userWithRole(RoleDefinitions::ROLE_HR);
        $this->actingAs($hr)->get('/admin/settings')->assertStatus(403);

        $admin = $this->userWithRole(RoleDefinitions::ROLE_ADMIN);
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
    }

    public function test_holidays_route_open_for_hr_and_admin_only(): void
    {
        $employee = $this->userWithRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($employee)->get('/holidays')->assertStatus(403);

        $hr = $this->userWithRole(RoleDefinitions::ROLE_HR);
        $this->actingAs($hr)->get('/holidays')->assertOk();
    }

    public function test_messages_route_open_for_all_roles(): void
    {
        foreach (RoleDefinitions::roles() as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get('/messages')->assertOk();
        }
    }

    public function test_org_chart_route_open_for_all_roles(): void
    {
        foreach (RoleDefinitions::roles() as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get('/org-chart')->assertOk();
        }
    }

    public function test_assets_route_open_for_all_roles(): void
    {
        // Sidebar item visible to everyone; controller scopes data per role
        // (manager only sees own assets, etc. — Walid 2026-04-30).
        foreach (RoleDefinitions::roles() as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get('/assets')->assertOk();
        }
    }

    public function test_documents_route_blocks_employee_and_manager(): void
    {
        $employee = $this->userWithRole(RoleDefinitions::ROLE_EMPLOYEE);
        $this->actingAs($employee)->get('/documents')->assertStatus(403);

        $manager = $this->userWithRole(RoleDefinitions::ROLE_MANAGER);
        $this->actingAs($manager)->get('/documents')->assertStatus(403);
    }

    public function test_documents_route_open_for_hr_and_admin(): void
    {
        foreach ([RoleDefinitions::ROLE_HR, RoleDefinitions::ROLE_ADMIN] as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get('/documents')->assertOk();
        }
    }

    public function test_departments_positions_offices_now_require_manage_permission(): void
    {
        // Locked 2026-04-30: these were sidebar items open to any
        // authenticated user; now they live under Settings (admin/HR only).
        $employee = $this->userWithRole(RoleDefinitions::ROLE_EMPLOYEE);
        $manager = $this->userWithRole(RoleDefinitions::ROLE_MANAGER);

        foreach (['/departments', '/positions', '/offices'] as $path) {
            $this->actingAs($employee)->get($path)->assertStatus(403);
            $this->actingAs($manager)->get($path)->assertStatus(403);
        }

        foreach ([RoleDefinitions::ROLE_HR, RoleDefinitions::ROLE_ADMIN] as $role) {
            $user = $this->userWithRole($role);
            foreach (['/departments', '/positions', '/offices'] as $path) {
                $this->actingAs($user)->get($path)->assertOk();
            }
        }
    }

    public function test_document_types_and_asset_categories_admin_only(): void
    {
        $hr = $this->userWithRole(RoleDefinitions::ROLE_HR);
        foreach (['/admin/document-types', '/admin/asset-categories'] as $path) {
            $this->actingAs($hr)->get($path)->assertStatus(403);
        }

        $admin = $this->userWithRole(RoleDefinitions::ROLE_ADMIN);
        foreach (['/admin/document-types', '/admin/asset-categories'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }
}
