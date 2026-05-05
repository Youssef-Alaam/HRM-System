<?php

namespace App\Repositories\Contracts;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function allForOrg(int $orgId): Collection;

    public function findOrFail(int $id): Department;

    public function create(array $data): Department;

    public function update(Department $department, array $data): Department;

    public function softDelete(Department $department): void;

    public function hasActiveEmployees(int $id): bool;
}
