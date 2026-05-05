<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Repositories\Contracts\LeaveCalendarRepositoryInterface;
use Illuminate\Support\Collection;

class LeaveCalendarRepository implements LeaveCalendarRepositoryInterface
{
    /** @param array<int> $employeeIds */
    public function entriesForPeriod(int $orgId, string $from, string $to, array $employeeIds = []): Collection
    {
        return LeaveRequest::query()
            ->where('org_id', $orgId)
            ->where('status', 'approved')
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$from, $to])
                ->orWhereBetween('end_date', [$from, $to])
                ->orWhere(fn ($q2) => $q2
                    ->where('start_date', '<=', $from)
                    ->where('end_date', '>=', $to)
                )
            )
            ->when($employeeIds !== [], fn ($q) => $q->whereIn('employee_id', $employeeIds))
            ->with(['employee.department', 'leaveType'])
            ->get();
    }

    public function holidaysForPeriod(int $orgId, string $from, string $to): Collection
    {
        return Holiday::query()
            ->where('org_id', $orgId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();
    }

    /** @return array<int> */
    public function teamEmployeeIds(int $managerEmployeeId): array
    {
        return Employee::query()
            ->where('manager_id', $managerEmployeeId)
            ->pluck('id')
            ->all();
    }

    /** @return array<int> */
    public function allEmployeeIds(int $orgId): array
    {
        return Employee::withoutGlobalScopes()
            ->where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();
    }
}
