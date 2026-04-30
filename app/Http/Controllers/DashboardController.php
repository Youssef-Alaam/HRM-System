<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        return Inertia::render('Dashboard', [
            'widgets' => fn () => $this->service->getDataForRole($user),
            // ?loading=1 forces the skeleton state for the F12.5 design demo.
            // Remove once the page is fed by real async data sources.
            'loading' => $request->boolean('loading'),
        ]);
    }
}
