<?php

namespace App\Repositories;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    protected function model(): string
    {
        return Department::class;
    }

    public function allForOrg(int $orgId): Collection
    {
        return Department::query()
            ->where('org_id', $orgId)
            ->with('parent:id,name')
            ->orderBy('name')
            ->get();
    }

    public function findOrFail(int $id): Department
    {
        /** @var Department */
        return Department::query()->findOrFail($id);
    }

    public function create(array $data): Department
    {
        /** @var Department */
        return Department::create($data);
    }

    public function update(Model $department, array $data): Department
    {
        $department->update($data);

        /** @var Department */
        return $department->fresh();
    }

    public function softDelete(Department $department): void
    {
        $department->delete();
    }

    public function hasActiveEmployees(int $id): bool
    {
        return Department::query()
            ->where('id', $id)
            ->whereHas('employees', fn ($q) => $q->where('employment_status', 'active'))
            ->exists();
    }
}
