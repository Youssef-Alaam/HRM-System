<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogService extends BaseService
{
    public function paginate(int $orgId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->where('org_id', $orgId)
            ->with('user:id,name,email')
            ->orderByDesc('id');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /** @return array<int, string> distinct action types for the filter dropdown */
    public function actionsForOrg(int $orgId): array
    {
        return AuditLog::query()
            ->where('org_id', $orgId)
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }
}
