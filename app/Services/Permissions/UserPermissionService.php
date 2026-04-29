<?php

namespace App\Services\Permissions;

use App\Models\AuditLog;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Wraps Spatie's per-user grant/revoke calls so every change is audit-logged.
 * No code outside this service should call $user->givePermissionTo() / revokePermissionTo()
 * for ad-hoc grants. (Role assignment via the admin UI also lands here.)
 */
class UserPermissionService
{
    public function grant(User $user, string $permission, string $reason): void
    {
        $this->ensureKnownPermission($permission);

        DB::transaction(function () use ($user, $permission, $reason) {
            $user->givePermissionTo($permission);

            $this->writeAudit('permission_granted', $user, [
                'permission' => $permission,
                'reason' => $reason,
            ]);
        });
    }

    public function revoke(User $user, string $permission, string $reason): void
    {
        $this->ensureKnownPermission($permission);

        DB::transaction(function () use ($user, $permission, $reason) {
            $user->revokePermissionTo($permission);

            $this->writeAudit('permission_revoked', $user, [
                'permission' => $permission,
                'reason' => $reason,
            ]);
        });
    }

    public function resetToRoleDefaults(User $user, string $reason): void
    {
        DB::transaction(function () use ($user, $reason) {
            $directPermissions = $user->permissions->pluck('name')->all();

            foreach ($directPermissions as $permission) {
                $user->revokePermissionTo($permission);
            }

            $this->writeAudit('permissions_reset_to_role_defaults', $user, [
                'cleared_direct_permissions' => $directPermissions,
                'reason' => $reason,
            ]);
        });
    }

    public function assignRole(User $user, string $role, string $reason): void
    {
        if (! in_array($role, RoleDefinitions::roles(), true)) {
            throw new InvalidArgumentException("Unknown role: {$role}");
        }

        DB::transaction(function () use ($user, $role, $reason) {
            $previous = $user->roles->pluck('name')->all();
            $user->syncRoles([$role]);

            $this->writeAudit('role_assigned', $user, [
                'previous_roles' => $previous,
                'new_role' => $role,
                'reason' => $reason,
            ]);
        });
    }

    private function ensureKnownPermission(string $permission): void
    {
        if (! in_array($permission, RoleDefinitions::allPermissions(), true)) {
            throw new InvalidArgumentException(
                "Unknown permission: {$permission}. Add it to RoleDefinitions::allPermissions() first."
            );
        }
    }

    private function writeAudit(string $action, User $target, array $changes): void
    {
        $actor = Auth::user();

        AuditLog::create([
            'org_id' => $target->org_id,
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => User::class,
            'entity_id' => $target->id,
            'changes' => array_merge($changes, [
                'target_user_id' => $target->id,
            ]),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
