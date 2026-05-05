<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('reports.run.own'), 403);

        $user = $request->user();
        $orgId = (int) $user->org_id;
        $canAny = $user->can('reports.run.any');
        $canTeam = $user->can('reports.run.team');

        $from = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->query('to', now()->format('Y-m-d'));

        $managerEmpId = $canTeam && ! $canAny && $user->employee_id
            ? (int) $user->employee_id
            : null;

        return Inertia::render('Reports/Index', [
            'can_any' => $canAny,
            'can_team' => $canTeam,
            'from' => $from,
            'to' => $to,
            'own_balance' => $this->serializeBalance($this->service->ownLeaveBalance($user)),
            'headcount_by_dept' => $canTeam
                ? $this->service->headcountByDepartment($orgId)->values()
                : null,
            'headcount_by_status' => $canAny
                ? $this->service->headcountByStatus($orgId)->values()
                : null,
            'leave_balances' => $canTeam
                ? $this->service->leaveBalanceSummary($orgId, $managerEmpId)
                    ->map(fn ($e) => [
                        'id' => $e->id,
                        'name' => $e->first_name.' '.$e->last_name,
                        'department' => $e->department,
                        'annual' => $e->annual_leave_balance_days,
                        'sick' => $e->sick_leave_balance_days,
                        'casual' => $e->casual_leave_balance_days,
                    ])->values()
                : null,
            'expiring_docs' => $canAny
                ? $this->service->expiringDocuments($orgId)
                    ->map(fn ($e) => [
                        'id' => $e->id,
                        'name' => $e->first_name.' '.$e->last_name,
                        'passport_expiry' => $e->passport_expiry?->format('Y-m-d'),
                        'work_permit_expiry' => $e->work_permit_expiry?->format('Y-m-d'),
                        'residency_permit_expiry' => $e->residency_permit_expiry?->format('Y-m-d'),
                    ])->values()
                : null,
            'overtime_summary' => $canTeam
                ? $this->service->overtimeSummary($orgId, $from, $to)
                    ->map(fn ($r) => [
                        'name' => $r->first_name.' '.$r->last_name,
                        'department' => $r->department,
                        'total_hours' => (float) $r->total_hours,
                        'requests_count' => (int) $r->requests_count,
                    ])->values()
                : null,
        ]);
    }

    private function serializeBalance(?object $emp): ?array
    {
        if (! $emp) {
            return null;
        }

        return [
            'annual' => $emp->annual_leave_balance_days,
            'sick' => $emp->sick_leave_balance_days,
            'casual' => $emp->casual_leave_balance_days,
        ];
    }
}
