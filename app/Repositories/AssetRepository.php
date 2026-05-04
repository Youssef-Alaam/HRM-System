<?php

namespace App\Repositories;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Repositories\Contracts\AssetRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AssetRepository extends BaseRepository implements AssetRepositoryInterface
{
    protected function model(): string
    {
        return Asset::class;
    }

    public function find(int $id): ?Asset
    {
        /** @var Asset|null */
        return parent::find($id);
    }

    public function findOrFail(int $id): Asset
    {
        /** @var Asset */
        return parent::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = $this->query()
            ->with(['category:id,name,icon_name', 'currentEmployee:id,first_name,last_name,employee_code']);

        foreach (['asset_category_id', 'current_status', 'current_employee_id'] as $key) {
            if (! empty($filters[$key])) {
                $q->where($key, $filters[$key]);
            }
        }

        if (! empty($filters['has_serial'])) {
            $q->whereNotNull('serial_number');
        }

        if (! empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $q->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('serial_number', 'like', $term)
                    ->orWhere('model', 'like', $term);
            });
        }

        return $q->orderBy('name')->paginate($perPage);
    }

    /**
     * @return Collection<int, Asset>
     */
    public function currentForEmployee(int $employeeId): Collection
    {
        /** @var Collection<int, Asset> */
        return $this->query()
            ->with('category:id,name,icon_name')
            ->where('current_employee_id', $employeeId)
            ->where('current_status', Asset::STATUS_ASSIGNED)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, AssetAssignment>
     */
    public function chainForAsset(int $assetId): Collection
    {
        /** @var Collection<int, AssetAssignment> */
        return AssetAssignment::query()
            ->with(['employee:id,first_name,last_name,employee_code', 'assignedBy:id,name', 'returnedBy:id,name'])
            ->where('asset_id', $assetId)
            ->orderByDesc('assigned_at')
            ->get();
    }

    public function create(array $data): Asset
    {
        /** @var Asset */
        return parent::create($data);
    }

    public function update(Model $asset, array $data): Asset
    {
        /** @var Asset */
        return parent::update($asset, $data);
    }

    public function softDelete(Asset $asset): bool
    {
        return (bool) $asset->delete();
    }
}
