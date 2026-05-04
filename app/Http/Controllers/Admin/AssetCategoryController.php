<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetCategoryRequest;
use App\Http\Requests\UpdateAssetCategoryRequest;
use App\Models\AssetCategory;
use App\Repositories\Contracts\AssetCategoryRepositoryInterface;
use App\Services\AssetCategoryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Settings → Asset categories (Admin only). Adding / editing /
 * disabling. Disable is blocked when the category still owns assets.
 */
class AssetCategoryController extends Controller
{
    public function __construct(
        private readonly AssetCategoryService $service,
        private readonly AssetCategoryRepositoryInterface $repo,
    ) {}

    public function index(Request $request)
    {
        $orgId = $request->user()->org_id;

        $categories = AssetCategory::query()
            ->where('org_id', $orgId)
            ->orderBy('order_index')
            ->orderBy('name')
            ->withCount('assets')
            ->get();

        return Inertia::render('Settings/AssetCategories', [
            'categories' => $categories->map(fn (AssetCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'icon_name' => $c->icon_name,
                'order_index' => (int) $c->order_index,
                'is_active' => (bool) $c->is_active,
                'assets_count' => $c->assets_count ?? 0,
            ])->values()->all(),
        ]);
    }

    public function store(StoreAssetCategoryRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('admin.asset-categories.index')
            ->with('status', 'Category added.');
    }

    public function update(int $category, UpdateAssetCategoryRequest $request): RedirectResponse
    {
        $this->service->update($category, $request->validated());

        return redirect()->route('admin.asset-categories.index')
            ->with('status', 'Category updated.');
    }

    public function destroy(int $category, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.asset_categories.manage'), 403);

        try {
            $this->service->disable($category);
        } catch (DomainException $e) {
            return back()->withErrors(['category' => $e->getMessage()]);
        }

        return redirect()->route('admin.asset-categories.index')
            ->with('status', 'Category disabled.');
    }
}
