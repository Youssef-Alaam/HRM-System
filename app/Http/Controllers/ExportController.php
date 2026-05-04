<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Exports\EmployeeDocumentsExport;
use App\Exports\EmployeesExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CSV / XLSX exports for the three list pages (queue item 9, locked
 * 2026-04-30). Format defaults to xlsx; pass ?format=csv for CSV.
 *
 * Permissions:
 * - Employees + Assets + Documents → exports.any (HR + Admin)
 * Tighter than the read permissions on purpose: bulk extracts are a
 * higher-trust action than browsing.
 */
class ExportController extends Controller
{
    public function employees(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('exports.any'), 403);

        $orgId = (int) $request->user()->org_id;
        $stamp = CarbonImmutable::now()->format('Ymd_His');
        [$ext, $writer] = $this->resolveFormat($request);

        return Excel::download(new EmployeesExport($orgId), "employees_{$stamp}.{$ext}", $writer);
    }

    public function assets(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('exports.any'), 403);

        $orgId = (int) $request->user()->org_id;
        $stamp = CarbonImmutable::now()->format('Ymd_His');
        [$ext, $writer] = $this->resolveFormat($request);

        return Excel::download(new AssetsExport($orgId), "assets_{$stamp}.{$ext}", $writer);
    }

    public function documents(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('exports.any'), 403);

        $orgId = (int) $request->user()->org_id;
        $stamp = CarbonImmutable::now()->format('Ymd_His');
        [$ext, $writer] = $this->resolveFormat($request);

        return Excel::download(new EmployeeDocumentsExport($orgId), "documents_{$stamp}.{$ext}", $writer);
    }

    /**
     * @return array{0: string, 1: string} [extension, writer-type]
     */
    private function resolveFormat(Request $request): array
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));

        return $format === 'csv'
            ? ['csv', ExcelType::CSV]
            : ['xlsx', ExcelType::XLSX];
    }
}
