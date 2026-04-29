<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Public registration is intentionally disabled. YZH HR is internal — accounts
 * are created by admins via Users & Roles. This test exists so re-enabling the
 * route requires deleting it deliberately, not by accident.
 */
class RegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_get_returns_404(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_register_post_returns_405_or_404(): void
    {
        $response = $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertContains(
            $response->getStatusCode(),
            [404, 405],
            'POST /register should not be accepted',
        );
    }

    public function test_register_route_is_not_registered_with_laravel(): void
    {
        $this->assertFalse(Route::has('register'));
    }
}
