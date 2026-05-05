<?php

namespace App\Services;

use App\Models\Office;
use App\Repositories\Contracts\OfficeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OfficeService extends BaseService
{
    public function __construct(private readonly OfficeRepositoryInterface $repo) {}

    public function listForOrg(int $orgId): Collection
    {
        return $this->repo->allForOrg($orgId);
    }

    public function create(int $orgId, array $data): Office
    {
        return $this->transaction(function () use ($orgId, $data) {
            return $this->repo->create(array_merge($data, ['org_id' => $orgId]));
        });
    }

    public function update(int $id, array $data): Office
    {
        $office = $this->repo->findOrFail($id);

        return $this->transaction(fn () => $this->repo->update($office, $data));
    }

    public function delete(int $id): void
    {
        $office = $this->repo->findOrFail($id);
        $this->transaction(fn () => $this->repo->softDelete($office));
    }
}
