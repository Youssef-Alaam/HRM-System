<?php

namespace App\Http\Controllers;

use App\Http\Requests\TerminateEmployeeRequest;
use App\Models\Employee;
use App\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    public function __construct(private readonly ComplianceService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('employees.terminate'), 403);

        $orgId = (int) $request->user()->org_id;

        return Inertia::render('Compliance/Index', [
            'probation' => $this->service->probationStatus($orgId)->values(),
            'retirement' => $this->service->retirementAlerts($orgId)->values(),
            'deemed' => $this->service->deemedResignationCandidates($orgId)->values(),
        ]);
    }

    public function terminationPreview(int $employee, Request $request): JsonResponse
    {
        abort_unless($request->user()->can('employees.terminate'), 403);

        $emp = Employee::findOrFail($employee);
        abort_unless((int) $emp->org_id === (int) $request->user()->org_id, 403);

        return response()->json([
            'eosb_piasters' => $this->service->calculateEosb($emp),
            'notice_period_days' => $this->service->noticePeriodDays($emp),
        ]);
    }

    public function terminate(int $employee, TerminateEmployeeRequest $request): RedirectResponse
    {
        $emp = Employee::findOrFail($employee);
        abort_unless((int) $emp->org_id === (int) $request->user()->org_id, 403);

        $this->service->terminate($emp, $request->user(), $request->validated());

        return redirect()->route('employees.show', $employee)
            ->with('status', 'Employee terminated.');
    }

    public function extendRetirement(int $employee, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('employees.terminate'), 403);

        $request->validate(['until' => ['required', 'date', 'after:today']]);

        $emp = Employee::findOrFail($employee);
        abort_unless((int) $emp->org_id === (int) $request->user()->org_id, 403);

        $this->service->extendRetirement($emp, $request->input('until'));

        return back()->with('status', 'Retirement extended.');
    }
}
