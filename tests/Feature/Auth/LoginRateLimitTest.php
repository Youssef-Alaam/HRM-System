<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\LoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('test@example.com|127.0.0.1');
        Cache::flush();
    }

    public function test_throttle_kicks_in_after_five_failed_attempts_within_window(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        for ($i = 0; $i < LoginService::MAX_ATTEMPTS_BEFORE_THROTTLE; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('Too many login attempts', $errors[0]);
    }

    public function test_successful_login_clears_throttle_counter(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(RateLimiter::tooManyAttempts('test@example.com|127.0.0.1', 1));
    }

    public function test_throttle_decay_is_fifteen_minutes(): void
    {
        $this->assertSame(900, LoginService::THROTTLE_DECAY_SECONDS);
    }
}
