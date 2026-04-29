<?php

namespace Database\Seeders;

use App\Permissions\RoleDefinitions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Idempotent: safe to re-run after adding permissions or shifting role bundles.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function () {
            foreach (RoleDefinitions::allPermissions() as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }

            foreach (RoleDefinitions::rolePermissions() as $roleName => $permissions) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $role->syncPermissions($permissions);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
