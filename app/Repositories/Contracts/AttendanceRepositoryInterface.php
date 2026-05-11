<?php

namespace App\Repositories\Contracts;

use App\Models\AttendanceRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AttendanceRepositoryInterface
{
    public function create(array $data): AttendanceRecord;

    public function findOrFail(int $id): AttendanceRecord;

    public function update(AttendanceRecord $record, array $data): AttendanceRecord;

    public function lastEventForEmployeeToday(int $orgId, int $employeeId, string $cairoDate): ?AttendanceRecord;

    public function todayForEmployee(int $orgId, int $employeeId, string $cairoDate): Collection;

    public function paginateForEmployee(int $orgId, int $employeeId): LengthAwarePaginator;

    public function paginateForOrg(int $orgId, array $filters): LengthAwarePaginator;

    /** @return Collection<int, AttendanceRecord> Selfies older than the cutoff that still have a path. */
    public function selfiesDueForPurge(\DateTimeInterface $cutoff): Collection;
}
