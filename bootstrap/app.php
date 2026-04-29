<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, \Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($request->expectsJson() || $request->is('up')) {
                return $response;
            }

            if (! in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            // Local debug: keep Whoops/Ignition for 500 so devs see stack traces.
            // Production (or APP_DEBUG=false) renders the branded Inertia page.
            if ($status === 500 && config('app.debug')) {
                return $response;
            }

            $props = ['status' => $status];

            if ($status === 503 && $response->headers->has('Retry-After')) {
                $props['retryAfter'] = (int) $response->headers->get('Retry-After');
            }

            return Inertia::render("Errors/{$status}", $props)
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
