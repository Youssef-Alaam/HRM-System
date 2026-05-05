<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Services\DepartmentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('org.departments.manage'), 403);

        $orgId = (int) $request->user()->org_id;

        return Inertia::render('Settings/Departments', [
            'departments' => $this->service->listForOrg($orgId)->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'description' => $d->description,
                'parent_department_id' => $d->parent_department_id,
                'parent_name' => $d->parent?->name,
                'is_active' => $d->is_active,
            ])->values(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->service->create((int) $request->user()->org_id, $request->validated());

        return back()->with('status', 'Department created.');
    }

    public function update(int $department, UpdateDepartmentRequest $request): RedirectResponse
    {
        $this->service->update($department, $request->validated());

        return back()->with('status', 'Department updated.');
    }

    public function destroy(int $department, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('org.departments.manage'), 403);

        try {
            $this->service->delete($department);
        } catch (DomainException $e) {
            return back()->withErrors(['department' => $e->getMessage()]);
        }

        return back()->with('status', 'Department deleted.');
    }
}
