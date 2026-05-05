<?php

namespace App\Http\Controllers;

use App\Exports\Form6Export;
use App\Exports\NosiExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GovernmentFilingController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('exports.any'), 403);

        return Inertia::render('GovernmentFilings/Index', [
            'current_month' => now()->format('Y-m'),
            'current_year' => (int) now()->format('Y'),
        ]);
    }

    public function nosi(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('exports.any'), 403);

        $orgId = (int) $request->user()->org_id;
        $month = $request->query('month', now()->format('Y-m'));
        $stamp = CarbonImmutable::now()->format('Ymd');

        return Excel::download(
            new NosiExport($orgId, $month),
            "nosi_{$month}_{$stamp}.xlsx",
        );
    }

    public function form6(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('exports.any'), 403);

        $orgId = (int) $request->user()->org_id;
        $year = (int) $request->query('year', now()->year);
        $stamp = CarbonImmutable::now()->format('Ymd');

        return Excel::download(
            new Form6Export($orgId, $year),
            "form6_{$year}_{$stamp}.xlsx",
        );
    }
}
