<?php

namespace App\Http\Controllers;

use App\Services\ForeignQuotaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ForeignQuotaController extends Controller
{
    public function __construct(private readonly ForeignQuotaService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('employees.view.any'), 403);

        $orgId = (int) $request->user()->org_id;

        return Inertia::render('ForeignQuota/Index', [
            'quota' => $this->service->quotaStatus($orgId),
            'expats' => $this->service->expatList($orgId),
        ]);
    }
}
