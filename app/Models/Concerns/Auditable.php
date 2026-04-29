<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Boots model event hooks that write before/after diffs to audit_logs on
 * created / updated / deleted / restored / force-deleted.
 *
 * Models can opt out of specific attributes by overriding auditExcludedAttributes().
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAuditEntry('created');
        });

        static::updated(function ($model) {
            $model->writeAuditEntry('updated');
        });

        static::deleted(function ($model) {
            $isForce = method_exists($model, 'isForceDeleting') && $model->isForceDeleting();
            $model->writeAuditEntry($isForce ? 'force_deleted' : 'deleted');
        });

        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::restored(function ($model) {
                $model->writeAuditEntry('restored');
            });
        }
    }

    public function writeAuditEntry(string $action): void
    {
        $changes = $this->buildAuditChanges($action);

        if ($action === 'updated' && empty($changes)) {
            return;
        }

        AuditLog::create([
            'org_id' => $this->resolveAuditOrgId(),
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => static::class,
            'entity_id' => (string) $this->getKey(),
            'changes' => $changes ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function auditExcludedAttributes(): array
    {
        return [
            'password',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    private function resolveAuditOrgId(): ?int
    {
        if (array_key_exists('org_id', $this->getAttributes())) {
            return $this->getAttribute('org_id');
        }

        return Auth::user()?->org_id;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAuditChanges(string $action): array
    {
        $excluded = $this->auditExcludedAttributes();

        return match ($action) {
            'created' => ['after' => $this->scrubAttributes($this->getAttributes(), $excluded)],
            'updated' => $this->buildUpdatedDiff($excluded),
            'deleted' => ['before' => $this->scrubAttributes($this->getOriginal(), $excluded)],
            'force_deleted' => ['before' => $this->scrubAttributes($this->getOriginal(), $excluded)],
            'restored' => ['after' => $this->scrubAttributes($this->getAttributes(), $excluded)],
            default => [],
        };
    }

    /**
     * @param  array<int, string>  $excluded
     * @return array<string, mixed>
     */
    private function buildUpdatedDiff(array $excluded): array
    {
        $dirty = array_diff_key($this->getDirty(), array_flip($excluded));

        if (empty($dirty)) {
            return [];
        }

        $before = [];
        foreach (array_keys($dirty) as $key) {
            $before[$key] = $this->getOriginal($key);
        }

        return [
            'before' => $before,
            'after' => $dirty,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $excluded
     * @return array<string, mixed>
     */
    private function scrubAttributes(array $attributes, array $excluded): array
    {
        return array_diff_key($attributes, array_flip($excluded));
    }
}
