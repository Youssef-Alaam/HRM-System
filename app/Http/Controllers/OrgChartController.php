<?php

namespace App\Http\Controllers;

use App\Services\OrgChartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only org chart at GET /org-chart. Open to every authenticated
 * user (no permission gate) per Feature 3 spec — clicking through to
 * an employee's detail page is what's gated, by the existing
 * `employees.view.*` policies on /employees/{id}.
 */
class OrgChartController extends Controller
{
    public function __construct(private readonly OrgChartService $service) {}

    public function index(Request $request): Response
    {
        $orgId = (int) $request->user()->org_id;

        return Inertia::render('OrgChart', [
            'roots' => $this->service->buildTree($orgId),
        ]);
    }
}
