<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\Holiday;
use Illuminate\Support\Collection;

interface ScheduleRepositoryInterface
{
    public function findEmployeeForUser(int $userId): ?Employee;

    /** @return Collection<int, Holiday> */
    public function getHolidaysForRange(int $orgId, string $from, string $to): Collection;
}
