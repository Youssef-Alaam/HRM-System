<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\LoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AccountLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_account_locks_after_ten_failed_attempts_within_a_day(): void
    {
        $user = User::factory()->create(['email' => 'lock@example.com']);

        // Use rotating IPs so per-IP throttle never trips before lock threshold.
        for ($i = 1; $i <= LoginService::MAX_FAILURES_PER_DAY_BEFORE_LOCK; $i++) {
            RateLimiter::clear('lock@example.com|127.0.0.'.$i);

            $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.'.$i])
                ->post('/login', [
                    'email' => 'lock@example.com',
                    'password' => 'wrong-password',
                ]);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_at, 'User should be locked after the threshold');
        $this->assertNotNull($user->lock_reason);
        $this->assertStringContainsString('Auto-lock', $user->lock_reason);
    }

    public function test_locked_user_cannot_login_even_with_correct_password(): void
    {
        $user = User::factory()->create([
            'email' => 'locked@example.com',
            'locked_at' => now(),
            'lock_reason' => 'Manual lock by admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'locked@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('locked', strtolower($errors[0]));
        $this->assertGuest();
    }

    public function test_account_lock_writes_audit_log_entry(): void
    {
        $user = User::factory()->create(['email' => 'lock-audit@example.com']);

        for ($i = 1; $i <= LoginService::MAX_FAILURES_PER_DAY_BEFORE_LOCK; $i++) {
            RateLimiter::clear('lock-audit@example.com|127.0.0.'.$i);
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.'.$i])
                ->post('/login', [
                    'email' => 'lock-audit@example.com',
                    'password' => 'wrong-password',
                ]);
        }

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account_locked',
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_can_unlock_account_by_clearing_locked_at(): void
    {
        $user = User::factory()->create([
            'email' => 'tobeunlocked@example.com',
            'locked_at' => now(),
            'lock_reason' => 'Auto-lock',
        ]);

        $user->forceFill(['locked_at' => null, 'lock_reason' => null])->save();

        $response = $this->post('/login', [
            'email' => 'tobeunlocked@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_warning_appears_when_two_attempts_remain_before_lock(): void
    {
        User::factory()->create(['email' => 'warn@example.com']);

        // First 7 wrong on rotating IPs (rotating to dodge per-IP throttle).
        for ($i = 1; $i <= 7; $i++) {
            RateLimiter::clear('warn@example.com|127.0.1.'.$i);
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.1.'.$i])
                ->post('/login', [
                    'email' => 'warn@example.com',
                    'password' => 'wrong',
                ]);
        }

        // 8th wrong → 2 remaining → warning should appear in the error message.
        RateLimiter::clear('warn@example.com|127.0.1.8');
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.1.8'])
            ->post('/login', [
                'email' => 'warn@example.com',
                'password' => 'wrong',
            ]);

        $response->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('2 attempt', $errors[0]);
        $this->assertStringContainsString('locked', strtolower($errors[0]));
    }

    public function test_no_warning_before_attempt_eight(): void
    {
        User::factory()->create(['email' => 'nowarn@example.com']);

        for ($i = 1; $i <= 7; $i++) {
            RateLimiter::clear('nowarn@example.com|127.0.2.'.$i);
            $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.2.'.$i])
                ->post('/login', [
                    'email' => 'nowarn@example.com',
                    'password' => 'wrong',
                ]);

            $errors = session('errors')->get('email');
            $this->assertStringNotContainsString('attempt(s) remaining', $errors[0] ?? '');
            $this->assertStringNotContainsString('locked', strtolower($errors[0] ?? ''));
        }
    }

    public function test_successful_login_does_not_clear_the_daily_failure_counter(): void
    {
        $user = User::factory()->create(['email' => 'mixed@example.com']);

        // 5 wrong → triggers per-IP throttle on this IP, so we rotate IPs.
        for ($i = 1; $i <= 5; $i++) {
            RateLimiter::clear('mixed@example.com|127.0.3.'.$i);
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.3.'.$i])
                ->post('/login', [
                    'email' => 'mixed@example.com',
                    'password' => 'wrong',
                ]);
        }

        // 1 correct login on a fresh IP — clears per-IP throttle but the daily
        // failure counter must persist (otherwise an attacker could pattern-mix).
        RateLimiter::clear('mixed@example.com|127.0.3.99');
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.3.99'])
            ->post('/login', [
                'email' => 'mixed@example.com',
                'password' => 'password',
            ]);
        $this->post('/logout');

        // 5 more wrong on fresh IPs → at attempt #10 in the 24h window the
        // account should lock, even though one success happened in between.
        for ($i = 10; $i <= 14; $i++) {
            RateLimiter::clear('mixed@example.com|127.0.3.'.$i);
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.3.'.$i])
                ->post('/login', [
                    'email' => 'mixed@example.com',
                    'password' => 'wrong',
                ]);
        }

        $user->refresh();
        $this->assertNotNull($user->locked_at, 'Account must lock at the 10th failure even if a success happened mid-stream');
    }
}
