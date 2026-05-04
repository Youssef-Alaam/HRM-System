<?php

namespace App\Repositories;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Repositories\Contracts\AssetCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AssetCategoryRepository extends BaseRepository implements AssetCategoryRepositoryInterface
{
    protected function model(): string
    {
        return AssetCategory::class;
    }

    public function find(int $id): ?AssetCategory
    {
        /** @var AssetCategory|null */
        return parent::find($id);
    }

    public function findOrFail(int $id): AssetCategory
    {
        /** @var AssetCategory */
        return parent::findOrFail($id);
    }

    /**
     * @return Collection<int, AssetCategory>
     */
    public function activeForOrg(int $orgId): Collection
    {
        /** @var Collection<int, AssetCategory> */
        return $this->query()
            ->where('org_id', $orgId)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): AssetCategory
    {
        /** @var AssetCategory */
        return parent::create($data);
    }

    public function update(Model $category, array $data): AssetCategory
    {
        /** @var AssetCategory */
        return parent::update($category, $data);
    }

    public function softDelete(AssetCategory $category): bool
    {
        $category->is_active = false;
        $category->save();

        return (bool) $category->delete();
    }

    public function hasAssets(int $categoryId): bool
    {
        return Asset::query()
            ->where('asset_category_id', $categoryId)
            ->exists();
    }
}
