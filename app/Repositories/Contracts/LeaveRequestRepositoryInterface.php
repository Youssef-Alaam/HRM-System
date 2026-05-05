<?php

namespace App\Repositories\Contracts;

use App\Models\LeaveRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LeaveRequestRepositoryInterface
{
    public function paginateForEmployee(int $employeeId, string $status): LengthAwarePaginator;

    public function find(int $id): ?LeaveRequest;

    /** @param array<string, mixed> $data */
    public function create(array $data): LeaveRequest;

    /** @param array<string, mixed> $data */
    public function updateRequest(LeaveRequest $request, array $data): LeaveRequest;

    public function hasOverlap(int $employeeId, string $start, string $end, ?int $excludeId = null): bool;

    public function usedDays(int $employeeId, int $leaveTypeId): int;
}
