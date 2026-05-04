<?php

namespace App\Repositories\Contracts;

use App\Models\EmployeeDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface EmployeeDocumentRepositoryInterface
{
    public function find(int $id): ?EmployeeDocument;

    public function findOrFail(int $id): EmployeeDocument;

    /**
     * Active (not soft-deleted) documents for the given employee.
     *
     * @return Collection<int, EmployeeDocument>
     */
    public function forEmployee(int $employeeId): Collection;

    /**
     * Active document for a specific (employee, type) pair, if any. Used
     * by the upload flow to find an existing slot to replace.
     */
    public function findByEmployeeAndType(int $employeeId, ?int $documentTypeId): ?EmployeeDocument;

    public function countActiveForEmployee(int $employeeId): int;

    /**
     * Paginated org-wide list with filters (employee, type, expiring).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EmployeeDocument;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $document, array $data): EmployeeDocument;

    public function softDelete(EmployeeDocument $document): bool;
}
