<?php

namespace App\Services\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginService
{
    public const MAX_ATTEMPTS_BEFORE_THROTTLE = 5;

    public const THROTTLE_DECAY_SECONDS = 900;          // 15 minutes

    public const MAX_FAILURES_PER_DAY_BEFORE_LOCK = 10;

    public const DAILY_FAILURE_WINDOW_SECONDS = 86400;  // 24 hours

    public function attempt(LoginRequest $request): void
    {
        $email = (string) $request->string('email');

        $this->ensureNotLocked($email, $request);
        $this->ensureNotRateLimited($request);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $this->recordFailure($email, $request);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $this->onSuccess($request);
    }

    public function logout(User $user, string $ip, ?string $userAgent): void
    {
        $this->writeAudit('logout', $user, $ip, $userAgent);
    }

    private function ensureNotLocked(string $email, LoginRequest $request): void
    {
        $user = User::withTrashed()->where('email', $email)->first();

        if ($user && $user->isLocked()) {
            $this->writeAudit('login_blocked_locked', $user, $request->ip(), $request->userAgent(), [
                'email' => $email,
                'lock_reason' => $user->lock_reason,
            ]);

            throw ValidationException::withMessages([
                'email' => trans('auth.locked'),
            ]);
        }
    }

    private function ensureNotRateLimited(LoginRequest $request): void
    {
        if (! RateLimiter::tooManyAttempts($request->throttleKey(), self::MAX_ATTEMPTS_BEFORE_THROTTLE)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($request->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function recordFailure(string $email, LoginRequest $request): void
    {
        RateLimiter::hit($request->throttleKey(), self::THROTTLE_DECAY_SECONDS);

        $user = User::withTrashed()->where('email', $email)->first();

        $this->writeAudit('login_failed', $user, $request->ip(), $request->userAgent(), [
            'email' => $email,
        ]);

        if (! $user) {
            return;
        }

        $dailyFailures = $this->incrementDailyFailureCounter($user->id);

        if ($dailyFailures >= self::MAX_FAILURES_PER_DAY_BEFORE_LOCK && ! $user->isLocked()) {
            $this->lockAccount($user, $request, sprintf(
                'Auto-lock: %d failed login attempts within 24h.',
                $dailyFailures,
            ));
        }
    }

    private function incrementDailyFailureCounter(int $userId): int
    {
        $key = "login.failures.daily.{$userId}";

        if (! Cache::has($key)) {
            Cache::put($key, 1, self::DAILY_FAILURE_WINDOW_SECONDS);

            return 1;
        }

        return (int) Cache::increment($key);
    }

    private function lockAccount(User $user, LoginRequest $request, string $reason): void
    {
        DB::transaction(function () use ($user, $reason) {
            $user->forceFill([
                'locked_at' => now(),
                'lock_reason' => Str::limit($reason, 200, ''),
            ])->save();
        });

        $this->writeAudit('account_locked', $user, $request->ip(), $request->userAgent(), [
            'reason' => $reason,
        ]);
    }

    private function onSuccess(LoginRequest $request): void
    {
        $user = $request->user();

        RateLimiter::clear($request->throttleKey());
        Cache::forget("login.failures.daily.{$user->id}");

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->writeAudit('login', $user, $request->ip(), $request->userAgent());
    }

    private function writeAudit(
        string $action,
        ?User $user,
        ?string $ip,
        ?string $userAgent,
        array $changes = [],
    ): void {
        AuditLog::create([
            'org_id' => $user?->org_id,
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $user ? User::class : null,
            'entity_id' => $user?->id,
            'changes' => $changes ?: null,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
