<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Models\Scopes\OrgScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Adds the OrgScope global scope and auto-fills org_id on creating
 * from the authenticated user's org. Provides withoutOrgScope($reason)
 * as the audited escape hatch for super-admin / cross-tenant operations.
 */
trait BelongsToOrg
{
    public static function bootBelongsToOrg(): void
    {
        static::addGlobalScope(new OrgScope);

        static::creating(function ($model) {
            if (! empty($model->org_id)) {
                return;
            }

            $orgId = Auth::user()?->org_id;
            if ($orgId !== null) {
                $model->org_id = $orgId;
            }
        });
    }

    /**
     * Returns a query builder with OrgScope removed AND writes an audit
     * entry for the bypass. The reason is required so the audit log
     * tells you WHY tenant isolation was crossed.
     */
    public static function withoutOrgScope(string $reason): Builder
    {
        $instance = new static;

        AuditLog::create([
            'org_id' => Auth::user()?->org_id,
            'user_id' => Auth::id(),
            'action' => 'org_scope_bypass',
            'entity_type' => static::class,
            'entity_id' => null,
            'changes' => ['reason' => $reason],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return $instance->newQueryWithoutScope(OrgScope::class);
    }
}
