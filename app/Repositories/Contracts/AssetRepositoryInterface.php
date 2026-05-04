<?php

namespace App\Repositories\Contracts;

use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface AssetRepositoryInterface
{
    public function find(int $id): ?Asset;

    public function findOrFail(int $id): Asset;

    /**
     * Paginated org-wide list with filters (category, status, employee).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /**
     * Currently-assigned assets for an employee (no history).
     *
     * @return Collection<int, Asset>
     */
    public function currentForEmployee(int $employeeId): Collection;

    /**
     * Full chain of custody for an asset, newest assignment first.
     *
     * @return Collection<int, AssetAssignment>
     */
    public function chainForAsset(int $assetId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Asset;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $asset, array $data): Asset;

    public function softDelete(Asset $asset): bool;
}
