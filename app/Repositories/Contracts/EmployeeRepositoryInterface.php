<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface EmployeeRepositoryInterface
{
    public function find(int $id): ?Employee;

    public function findOrFail(int $id): Employee;

    /**
     * Paginated roster, filtered to scope (employee = self only,
     * manager = own team, HR/admin = whole org). Caller passes the scope.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee;

    /**
     * Parameter typed as base Model so concrete implementations can extend
     * BaseRepository::update(Model). Caller must pass an Employee.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $employee, array $data): Employee;

    public function softDelete(Employee $employee, string $reason): bool;

    public function generateEmployeeCode(int $orgId): string;
}
