<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Constrains queries on tenant-aware models to the actor's org_id.
 * No-op when there is no authenticated user (console, seeders, queue
 * jobs running with a system context). Use Model::withoutOrgScope() to
 * bypass intentionally — that path writes an audit log entry.
 */
class OrgScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || ! isset($user->org_id) || $user->org_id === null) {
            return;
        }

        $builder->where($model->getTable().'.org_id', $user->org_id);
    }
}
