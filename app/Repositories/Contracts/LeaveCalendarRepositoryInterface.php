<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface LeaveCalendarRepositoryInterface
{
    /** @param array<int> $employeeIds */
    public function entriesForPeriod(int $orgId, string $from, string $to, array $employeeIds = []): Collection;

    public function holidaysForPeriod(int $orgId, string $from, string $to): Collection;

    /** @return array<int> */
    public function teamEmployeeIds(int $managerEmployeeId): array;

    /** @return array<int> */
    public function allEmployeeIds(int $orgId): array;
}
