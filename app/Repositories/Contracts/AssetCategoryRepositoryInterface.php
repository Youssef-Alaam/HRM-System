<?php

namespace App\Repositories\Contracts;

use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface AssetCategoryRepositoryInterface
{
    public function find(int $id): ?AssetCategory;

    public function findOrFail(int $id): AssetCategory;

    /**
     * @return Collection<int, AssetCategory>
     */
    public function activeForOrg(int $orgId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AssetCategory;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $category, array $data): AssetCategory;

    public function softDelete(AssetCategory $category): bool;

    public function hasAssets(int $categoryId): bool;
}
