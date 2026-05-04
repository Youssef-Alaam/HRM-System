<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assets\AssignAssetRequest;
use App\Http\Requests\Assets\MarkIncidentRequest;
use App\Http\Requests\Assets\ReturnAssetRequest;
use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Employee;
use App\Repositories\Contracts\AssetCategoryRepositoryInterface;
use App\Repositories\Contracts\AssetRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\AssetService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Sidebar landing + CRUD for assets.
 *
 * Locked 2026-04-30 with Walid:
 * - Employee + Manager → only their own currently-assigned assets.
 *   Manager does NOT see their team's. Yes, unusual; yes, explicit.
 * - HR + Admin → all assets in the org with filters + chain history.
 */
class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService $service,
        private readonly AssetRepositoryInterface $repo,
        private readonly AssetCategoryRepositoryInterface $categories,
        private readonly EmployeeRepositoryInterface $employees,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $canViewAny = $user->can('assets.view.any');

        if ($canViewAny) {
            $filters = $request->only([
                'asset_category_id', 'current_status', 'current_employee_id',
                'has_serial', 'search',
            ]);
            $assets = $this->repo->paginate(25, $filters);
            $categories = $this->categories->activeForOrg((int) $user->org_id);

            return Inertia::render('Assets/Index', [
                'mode' => 'org',
                'assets' => $assets,
                'categories' => $categories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'icon_name' => $c->icon_name,
                ])->values(),
                'filters' => $filters,
                'canCreate' => $user->can('assets.create'),
                'canAssign' => $user->can('assets.assign'),
                'statuses' => [
                    Asset::STATUS_IN_POOL,
                    Asset::STATUS_ASSIGNED,
                    Asset::STATUS_LOST,
                    Asset::STATUS_DAMAGED,
                    Asset::STATUS_WRITTEN_OFF,
                ],
            ]);
        }

        // Employee + Manager — own currently-assigned only.
        $employeeId = (int) ($user->employee_id ?? 0);
        $own = $employeeId
            ? $this->repo->currentForEmployee($employeeId)
            : collect();

        return Inertia::render('Assets/Index', [
            'mode' => 'self',
            'own' => $own->map(fn (Asset $a) => $this->serializeAsset($a))->values(),
        ]);
    }

    public function show(int $asset, Request $request)
    {
        abort_unless($request->user()->can('assets.view.any'), 403);

        $row = $this->repo->findOrFail($asset);
        $row->load(['category', 'currentEmployee']);
        $chain = $this->repo->chainForAsset($asset);

        $assignableEmployees = $this->employees->query()
            ->where('employment_status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'employee_code' => $e->employee_code,
                'name' => trim($e->first_name.' '.$e->last_name),
            ])->values();

        return Inertia::render('Assets/Show', [
            'asset' => $this->serializeAsset($row, withCategoryDetail: true),
            'chain' => $chain->map(fn ($a) => [
                'id' => $a->id,
                'employee' => $a->employee
                    ? [
                        'id' => $a->employee->id,
                        'employee_code' => $a->employee->employee_code,
                        'name' => trim($a->employee->first_name.' '.$a->employee->last_name),
                    ]
                    : null,
                'assigned_at' => optional($a->assigned_at)->toIso8601String(),
                'returned_at' => optional($a->returned_at)->toIso8601String(),
                'expected_return_at' => optional($a->expected_return_at)->toIso8601String(),
                'age_at_assignment_months' => $a->age_at_assignment_months,
                'condition_at_assignment' => $a->condition_at_assignment,
                'return_condition' => $a->return_condition,
                'return_notes' => $a->return_notes,
                'assigned_by' => $a->assignedBy?->name,
                'returned_by' => $a->returnedBy?->name,
                'notes' => $a->notes,
            ])->values(),
            'assignableEmployees' => $assignableEmployees,
            'canAssign' => $request->user()->can('assets.assign'),
            'canDelete' => $request->user()->can('assets.delete'),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('assets.create'), 403);

        $categories = $this->categories->activeForOrg((int) $request->user()->org_id);

        return Inertia::render('Assets/Create', [
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $asset = $this->service->create($request->validated());

        return redirect()->route('assets.show', $asset->id)
            ->with('status', 'Asset created.');
    }

    public function update(int $asset, UpdateAssetRequest $request): RedirectResponse
    {
        $this->service->update($asset, $request->validated());

        return back()->with('status', 'Asset updated.');
    }

    public function assign(int $asset, AssignAssetRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail((int) $request->input('employee_id'));

        try {
            $this->service->assign(
                assetId: $asset,
                employee: $employee,
                conditionAtAssignment: (string) $request->input('condition_at_assignment'),
                expectedReturnAt: $request->input('expected_return_at'),
                notes: $request->input('notes'),
                assignedByUserId: (int) $request->user()->id,
            );
        } catch (DomainException $e) {
            return back()->withErrors(['employee_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Asset assigned.');
    }

    public function return(int $asset, ReturnAssetRequest $request): RedirectResponse
    {
        try {
            $this->service->returnAsset(
                assetId: $asset,
                returnCondition: (string) $request->input('return_condition'),
                returnNotes: $request->input('return_notes'),
                returnedByUserId: (int) $request->user()->id,
            );
        } catch (DomainException $e) {
            return back()->withErrors(['return_condition' => $e->getMessage()]);
        }

        return back()->with('status', 'Asset returned.');
    }

    public function markLost(int $asset, MarkIncidentRequest $request): RedirectResponse
    {
        $this->service->markLost($asset, (string) $request->input('notes'), (int) $request->user()->id);

        return back()->with('status', 'Asset marked lost.');
    }

    public function markDamaged(int $asset, MarkIncidentRequest $request): RedirectResponse
    {
        $this->service->markDamaged($asset, (string) $request->input('notes'), (int) $request->user()->id);

        return back()->with('status', 'Asset marked damaged.');
    }

    public function destroy(int $asset, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('assets.delete'), 403);

        try {
            $this->service->softDelete($asset);
        } catch (DomainException $e) {
            return back()->withErrors(['asset' => $e->getMessage()]);
        }

        return redirect()->route('assets.index')->with('status', 'Asset deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAsset(Asset $asset, bool $withCategoryDetail = false): array
    {
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'serial_number' => $asset->serial_number,
            'model' => $asset->model,
            'value_piasters' => (int) $asset->value_piasters,
            'acquired_date' => optional($asset->acquired_date)->toDateString(),
            'condition_at_acquisition' => $asset->condition_at_acquisition,
            'current_status' => $asset->current_status,
            'notes' => $asset->notes,
            'category' => $withCategoryDetail
                ? [
                    'id' => $asset->category?->id,
                    'name' => $asset->category?->name,
                    'icon_name' => $asset->category?->icon_name,
                ]
                : ($asset->category ? [
                    'id' => $asset->category->id,
                    'name' => $asset->category->name,
                ] : null),
            'current_employee' => $asset->currentEmployee
                ? [
                    'id' => $asset->currentEmployee->id,
                    'employee_code' => $asset->currentEmployee->employee_code,
                    'name' => trim($asset->currentEmployee->first_name.' '.$asset->currentEmployee->last_name),
                ]
                : null,
        ];
    }
}
