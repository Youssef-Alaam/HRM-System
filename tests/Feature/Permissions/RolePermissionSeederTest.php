<?php

namespace Tests\Feature\Permissions;

use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_seeder_creates_all_four_roles(): void
    {
        $this->assertSame(4, Role::count());

        foreach (RoleDefinitions::roles() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_seeder_creates_all_permissions_in_catalog(): void
    {
        $this->assertSame(
            count(RoleDefinitions::allPermissions()),
            Permission::count()
        );

        foreach (RoleDefinitions::allPermissions() as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
        }
    }

    public function test_admin_role_has_every_permission(): void
    {
        $admin = Role::findByName(RoleDefinitions::ROLE_ADMIN);

        $this->assertSame(
            count(RoleDefinitions::allPermissions()),
            $admin->permissions()->count()
        );
    }

    public function test_employee_role_has_only_self_scoped_permissions(): void
    {
        $employee = Role::findByName(RoleDefinitions::ROLE_EMPLOYEE);
        $names = $employee->permissions->pluck('name')->all();

        $this->assertContains('employees.view.own', $names);
        $this->assertContains('leave.request.own', $names);
        $this->assertNotContains('employees.view.team', $names);
        $this->assertNotContains('leave.approve.team', $names);
        $this->assertNotContains('payroll.run', $names);
    }

    public function test_manager_inherits_employee_permissions_plus_team_scope(): void
    {
        $manager = Role::findByName(RoleDefinitions::ROLE_MANAGER);
        $names = $manager->permissions->pluck('name')->all();

        $this->assertContains('employees.view.own', $names);
        $this->assertContains('employees.view.team', $names);
        $this->assertContains('leave.approve.team', $names);
        $this->assertContains('attendance.assign_shifts.team', $names);
        $this->assertNotContains('employees.view.any', $names);
        $this->assertNotContains('leave.approve.final', $names);
        $this->assertNotContains('payroll.run', $names);
    }

    public function test_hr_inherits_manager_permissions_plus_org_wide(): void
    {
        $hr = Role::findByName(RoleDefinitions::ROLE_HR);
        $names = $hr->permissions->pluck('name')->all();

        $this->assertContains('employees.view.team', $names);
        $this->assertContains('employees.view.any', $names);
        $this->assertContains('payroll.run', $names);
        $this->assertContains('leave.approve.final', $names);
        $this->assertNotContains('users.assign_roles', $names);
        $this->assertNotContains('audit.view', $names);
    }

    public function test_only_admin_can_assign_roles_or_view_audit_log(): void
    {
        foreach ([RoleDefinitions::ROLE_EMPLOYEE, RoleDefinitions::ROLE_MANAGER, RoleDefinitions::ROLE_HR] as $name) {
            $role = Role::findByName($name);
            $perms = $role->permissions->pluck('name')->all();
            $this->assertNotContains('users.assign_roles', $perms, "{$name} should not have users.assign_roles");
            $this->assertNotContains('audit.view', $perms, "{$name} should not have audit.view");
            $this->assertNotContains('settings.edit', $perms, "{$name} should not have settings.edit");
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(4, Role::count());
        $this->assertSame(count(RoleDefinitions::allPermissions()), Permission::count());
    }
}
