<?php

namespace App\Services;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService extends BaseService
{
    public function __construct(private readonly DepartmentRepositoryInterface $repo) {}

    public function listForOrg(int $orgId): Collection
    {
        return $this->repo->allForOrg($orgId);
    }

    public function create(int $orgId, array $data): Department
    {
        return $this->transaction(function () use ($orgId, $data) {
            return $this->repo->create(array_merge($data, ['org_id' => $orgId]));
        });
    }

    public function update(int $id, array $data): Department
    {
        $dept = $this->repo->findOrFail($id);

        return $this->transaction(fn () => $this->repo->update($dept, $data));
    }

    public function delete(int $id): void
    {
        if ($this->repo->hasActiveEmployees($id)) {
            throw new DomainException('Cannot delete a department that has active employees.');
        }

        $dept = $this->repo->findOrFail($id);
        $this->transaction(fn () => $this->repo->softDelete($dept));
    }
}
