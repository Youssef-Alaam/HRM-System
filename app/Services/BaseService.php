<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Layer 4 base — concrete services extend this. The two helpers below are
 * the entire surface area; everything else is domain code.
 *
 * Audit logging is NOT a service responsibility. Auditable (model trait)
 * fires on created/updated/deleted/restored via Eloquent events. The only
 * obligation here is: do every multi-step write inside transaction() so the
 * model events commit (or roll back) atomically with the parent operation.
 */
abstract class BaseService
{
    /**
     * Wrap a multi-step write so model events (Auditable, BelongsToOrg)
     * commit or roll back atomically with the parent operation.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Service-level logger for business decisions, not for model writes
     * (those are captured by the audit log).
     */
    protected function log(): LoggerInterface
    {
        return Log::channel(config('logging.default'));
    }
}
