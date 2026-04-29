<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_successful_login_writes_audit_entry(): void
    {
        $user = User::factory()->create(['email' => 'audit-login@example.com']);

        $this->post('/login', [
            'email' => 'audit-login@example.com',
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'user_id' => $user->id,
            'entity_type' => User::class,
        ]);

        $audit = AuditLog::where('user_id', $user->id)->where('action', 'login')->first();
        $this->assertSame('127.0.0.1', $audit->ip_address);
    }

    public function test_failed_login_writes_audit_entry(): void
    {
        User::factory()->create(['email' => 'audit-fail@example.com']);

        $this->post('/login', [
            'email' => 'audit-fail@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_failed',
        ]);
    }

    public function test_logout_writes_audit_entry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_login_updates_last_login_metadata(): void
    {
        $user = User::factory()->create([
            'email' => 'last-login@example.com',
            'last_login_at' => null,
        ]);

        $this->post('/login', [
            'email' => 'last-login@example.com',
            'password' => 'password',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertSame('127.0.0.1', $user->last_login_ip);
    }

    public function test_password_reset_link_request_writes_audit_entry(): void
    {
        User::factory()->create(['email' => 'pwreset@example.com']);

        $this->post('/forgot-password', [
            'email' => 'pwreset@example.com',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_link_requested',
        ]);
    }

    public function test_audit_log_captures_ip_and_user_agent(): void
    {
        $user = User::factory()->create(['email' => 'meta@example.com']);

        $this->withHeaders(['User-Agent' => 'TestBrowser/1.0'])
            ->post('/login', [
                'email' => 'meta@example.com',
                'password' => 'password',
            ]);

        $audit = AuditLog::where('user_id', $user->id)->where('action', 'login')->first();
        $this->assertNotNull($audit);
        $this->assertSame('127.0.0.1', $audit->ip_address);
        $this->assertStringContainsString('TestBrowser', $audit->user_agent);
    }
}
