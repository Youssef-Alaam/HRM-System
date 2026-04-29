<?php

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Error pages routing test (F12.5)
|--------------------------------------------------------------------------
| Asserts that bootstrap/app.php's withExceptions(...) hook routes the six
| HTTP status codes through Inertia to the Pages/Errors/{code}.tsx pages.
|
| 500 is debug-gated: in local debug mode Whoops/Ignition still wins, so we
| explicitly disable debug in that test only.
*/

beforeEach(function () {
    Route::get('/_test/abort/{code}', fn (int $code) => abort($code));
});

it('renders the 403 page through Inertia on Forbidden', function () {
    $this->get('/_test/abort/403')
        ->assertStatus(403)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/403')
            ->where('status', 403));
});

it('renders the 404 page through Inertia on Not Found', function () {
    $this->get('/_test/abort/404')
        ->assertStatus(404)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/404')
            ->where('status', 404));
});

it('renders the 404 page on a non-existent URL', function () {
    $this->get('/this-route-does-not-exist-' . uniqid())
        ->assertStatus(404)
        ->assertInertia(fn (Assert $page) => $page->component('Errors/404'));
});

it('renders the 419 page through Inertia on Page Expired', function () {
    $this->get('/_test/abort/419')
        ->assertStatus(419)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/419')
            ->where('status', 419));
});

it('renders the 429 page through Inertia on Too Many Requests', function () {
    $this->get('/_test/abort/429')
        ->assertStatus(429)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/429')
            ->where('status', 429));
});

it('renders the 500 page through Inertia when debug is off', function () {
    config()->set('app.debug', false);

    $this->get('/_test/abort/500')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/500')
            ->where('status', 500));
});

it('keeps Whoops/Ignition for 500 when debug is on', function () {
    config()->set('app.debug', true);

    // With debug on, the response shouldn't be Inertia — it's the framework's
    // debug page (HTML or JSON depending on Accept). We just assert it's not
    // the branded Inertia component.
    $response = $this->get('/_test/abort/500');

    $response->assertStatus(500);

    $body = $response->getContent();
    expect($body)->not->toContain('"component":"Errors\\/500"');
});

it('renders the 503 page through Inertia on Service Unavailable', function () {
    $this->get('/_test/abort/503')
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/503')
            ->where('status', 503));
});

it('passes retryAfter to the 503 page when the header is present', function () {
    Route::get('/_test/maintenance', function () {
        throw new \Symfony\Component\HttpKernel\Exception\HttpException(
            503,
            'Down for maintenance',
            null,
            ['Retry-After' => 120],
        );
    });

    $this->get('/_test/maintenance')
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/503')
            ->where('retryAfter', 120));
});

it('does not intercept JSON requests', function () {
    $this->getJson('/_test/abort/404')
        ->assertStatus(404)
        ->assertJson(['message' => '']);
});
