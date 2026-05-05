<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Services\PositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function __construct(
        private readonly PositionService $service,
        private readonly DepartmentRepositoryInterface $departments,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('org.positions.manage'), 403);

        $orgId = (int) $request->user()->org_id;

        return Inertia::render('Settings/Positions', [
            'positions' => $this->service->listForOrg($orgId)->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'description' => $p->description,
                'level' => $p->level,
                'department_id' => $p->department_id,
                'department_name' => $p->department?->name,
                'is_active' => $p->is_active,
            ])->values(),
            'departments' => $this->departments->allForOrg($orgId)->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
            ])->values(),
        ]);
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $this->service->create((int) $request->user()->org_id, $request->validated());

        return back()->with('status', 'Position created.');
    }

    public function update(int $position, UpdatePositionRequest $request): RedirectResponse
    {
        $this->service->update($position, $request->validated());

        return back()->with('status', 'Position updated.');
    }

    public function destroy(int $position, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('org.positions.manage'), 403);

        $this->service->delete($position);

        return back()->with('status', 'Position deleted.');
    }
}
