<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Support\Collection;

class ScheduleRepository extends BaseRepository implements ScheduleRepositoryInterface
{
    protected function model(): string
    {
        return Employee::class;
    }

    public function findEmployeeForUser(int $userId): ?Employee
    {
        $user = User::find($userId);
        if (! $user || ! $user->employee_id) {
            return null;
        }

        return Employee::query()->find($user->employee_id);
    }

    /** @return Collection<int, Holiday> */
    public function getHolidaysForRange(int $orgId, string $from, string $to): Collection
    {
        return Holiday::query()
            ->where('org_id', $orgId)
            ->whereBetween('date', [$from, $to])
            ->get();
    }
}
