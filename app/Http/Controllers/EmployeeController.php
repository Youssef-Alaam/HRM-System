<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Feature 2 — Employees CRUD.
 *
 * index/show render Inertia pages for browser navigation; explicit JSON
 * requests (Accept: application/json) return the raw data shape so Tinker
 * and future API consumers don't go through the Inertia layer.
 */
class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $service,
        private readonly EmployeeRepositoryInterface $repo,
    ) {}

    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->can('employees.view.team') || $request->user()?->can('employees.view.any'),
            403,
        );

        $filters = $request->only(['search', 'department_id', 'position_id', 'office_id', 'employment_status']);

        // Manager scope: only own team. HR/Admin: whole org (OrgScope handles).
        if ($request->user()->can('employees.view.any') === false
            && $request->user()->can('employees.view.team')) {
            $filters['manager_id'] = $request->user()->employee_id;
        }

        $employees = $this->repo->paginate(25, $filters);

        if ($request->wantsJson()) {
            return response()->json(['data' => $employees]);
        }

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => $filters,
            'canCreate' => $request->user()->can('employees.create'),
        ]);
    }

    public function show(int $employee, Request $request)
    {
        $row = $this->repo->findOrFail($employee);

        // Self-view always allowed; otherwise need team or any.
        $isSelf = $request->user()->employee_id === $row->id;
        $canViewAny = $request->user()->can('employees.view.any');
        $canViewTeam = $request->user()->can('employees.view.team') && $row->manager_id === $request->user()->employee_id;

        if (! $isSelf && ! $canViewAny && ! $canViewTeam) {
            abort(403);
        }

        $row->load(['department', 'position', 'office', 'manager']);

        if ($request->wantsJson()) {
            return response()->json(['data' => $row]);
        }

        return Inertia::render('Employees/Show', [
            'employee' => $row,
            'canDelete' => $request->user()->can('employees.delete'),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->service->create($request->validated());

        return redirect()->route('employees.show', $employee->id)
            ->with('status', 'Employee created.');
    }

    public function destroy(int $employee, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('employees.delete'), 403);

        $reason = (string) $request->input('reason', '');
        if (trim($reason) === '') {
            return back()->withErrors(['reason' => 'A reason is required to soft-delete an employee.']);
        }

        $this->service->softDelete($employee, $reason);

        return redirect()->route('employees.index')
            ->with('status', 'Employee soft-deleted.');
    }
}
