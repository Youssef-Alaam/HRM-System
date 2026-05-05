<?php

namespace App\Services;

use App\Models\Position;
use App\Repositories\Contracts\PositionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PositionService extends BaseService
{
    public function __construct(private readonly PositionRepositoryInterface $repo) {}

    public function listForOrg(int $orgId): Collection
    {
        return $this->repo->allForOrg($orgId);
    }

    public function create(int $orgId, array $data): Position
    {
        return $this->transaction(function () use ($orgId, $data) {
            return $this->repo->create(array_merge($data, ['org_id' => $orgId]));
        });
    }

    public function update(int $id, array $data): Position
    {
        $pos = $this->repo->findOrFail($id);

        return $this->transaction(fn () => $this->repo->update($pos, $data));
    }

    public function delete(int $id): void
    {
        $pos = $this->repo->findOrFail($id);
        $this->transaction(fn () => $this->repo->softDelete($pos));
    }
}
