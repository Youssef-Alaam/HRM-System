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
}
