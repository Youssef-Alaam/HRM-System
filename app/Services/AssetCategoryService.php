<?php

namespace App\Services;

use App\Models\AssetCategory;
use App\Repositories\Contracts\AssetCategoryRepositoryInterface;
use DomainException;

/**
 * Layer-3 service for asset categories. Admin-only writes; authorization
 * is handled by the FormRequest. Block delete when assets still reference
 * the category — soft-delete instead of breaking historical references.
 */
class AssetCategoryService extends BaseService
{
    public function __construct(
        private readonly AssetCategoryRepositoryInterface $repo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AssetCategory
    {
        return $this->transaction(function () use ($data) {
            return $this->repo->create(array_merge([
                'org_id' => auth()->user()->org_id,
                'is_active' => true,
            ], $data));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): AssetCategory
    {
        return $this->transaction(function () use ($id, $data) {
            $category = $this->repo->findOrFail($id);

            return $this->repo->update($category, $data);
        });
    }

    public function disable(int $id): void
    {
        $this->transaction(function () use ($id) {
            $category = $this->repo->findOrFail($id);

            if ($this->repo->hasAssets($id)) {
                throw new DomainException(
                    'Cannot disable a category that still has assets. Reassign or write off the assets first.',
                );
            }

            $this->repo->softDelete($category);
        });
    }
}
