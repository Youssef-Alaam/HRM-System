<?php

namespace App\Repositories;

use App\Models\Position;
use App\Repositories\Contracts\PositionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PositionRepository extends BaseRepository implements PositionRepositoryInterface
{
    protected function model(): string
    {
        return Position::class;
    }

    public function allForOrg(int $orgId): Collection
    {
        return Position::query()
            ->where('org_id', $orgId)
            ->with('department:id,name')
            ->orderBy('department_id')
            ->orderBy('title')
            ->get();
    }

    public function findOrFail(int $id): Position
    {
        /** @var Position */
        return Position::query()->findOrFail($id);
    }

    public function create(array $data): Position
    {
        /** @var Position */
        return Position::create($data);
    }

    public function update(Model $position, array $data): Position
    {
        $position->update($data);

        /** @var Position */
        return $position->fresh();
    }

    public function softDelete(Position $position): void
    {
        $position->delete();
    }
}
