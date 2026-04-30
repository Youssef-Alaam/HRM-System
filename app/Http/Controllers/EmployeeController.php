<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * Feature 2 — Employees CRUD.
 *
 * For now this controller exposes JSON-style responses + redirect-on-store
 * so the test suite can drive validation. Feature 9 (Settings) and the
 * Employees frontend pages will swap these to Inertia::render() with full
 * pagination/search params.
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

        // Manager scope: only own team. HR/Admin: all in org (OrgScope handles).
        if ($request->user()->can('employees.view.any') === false
            && $request->user()->can('employees.view.team')) {
            $filters['manager_id'] = $request->user()->employee_id;
        }

        return response()->json([
            'data' => $this->repo->paginate(25, $filters),
        ]);
    }

    public function show(int $employee, Request $request)
    {
        $row = $this->repo->findOrFail($employee);

        // Self-view always allowed; otherwise need team or any.
        $isSelf = $request->user()->employee_id === $row->id;
        if (! $isSelf
            && ! $request->user()->can('employees.view.any')
            && ! ($request->user()->can('employees.view.team') && $row->manager_id === $request->user()->employee_id)) {
            abort(403);
        }

        return response()->json(['data' => $row]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->service->create($request->validated());

        return Redirect::route('employees.show', $employee->id)
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

        return Redirect::route('employees.index')
            ->with('status', 'Employee soft-deleted.');
    }
}
