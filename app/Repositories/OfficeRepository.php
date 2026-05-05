<?php

namespace App\Repositories;

use App\Models\Office;
use App\Repositories\Contracts\OfficeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OfficeRepository extends BaseRepository implements OfficeRepositoryInterface
{
    protected function model(): string
    {
        return Office::class;
    }

    public function allForOrg(int $orgId): Collection
    {
        return Office::query()
            ->where('org_id', $orgId)
            ->orderBy('name')
            ->get();
    }

    public function findOrFail(int $id): Office
    {
        /** @var Office */
        return Office::query()->findOrFail($id);
    }

    public function create(array $data): Office
    {
        /** @var Office */
        return Office::create($data);
    }

    public function update(Model $office, array $data): Office
    {
        $office->update($data);

        /** @var Office */
        return $office->fresh();
    }

    public function softDelete(Office $office): void
    {
        $office->delete();
    }
}
