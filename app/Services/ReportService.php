<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OtherRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService extends BaseService
{
    public function headcountByDepartment(int $orgId): Collection
    {
        return Employee::query()
            ->where('employees.org_id', $orgId)
            ->where('employment_status', 'active')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->select('departments.name as department', DB::raw('count(*) as total'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get();
    }

    public function headcountByStatus(int $orgId): Collection
    {
        return Employee::withoutGlobalScopes()
            ->where('employees.org_id', $orgId)
            ->select('employment_status', DB::raw('count(*) as total'))
            ->groupBy('employment_status')
            ->orderByDesc('total')
            ->get();
    }

    public function leaveBalanceSummary(int $orgId, ?int $managerEmployeeId = null): Collection
    {
        $query = Employee::query()
            ->where('employees.org_id', $orgId)
            ->where('employment_status', 'active')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->select(
                'employees.id',
                'employees.first_name',
                'employees.last_name',
                'departments.name as department',
                'annual_leave_balance_days',
                'sick_leave_balance_days',
                'casual_leave_balance_days',
            )
            ->orderBy('employees.last_name')
            ->orderBy('employees.first_name');

        if ($managerEmployeeId) {
            $query->where('manager_id', $managerEmployeeId);
        }

        return $query->get();
    }

    public function expiringDocuments(int $orgId, int $daysAhead = 60): Collection
    {
        $cutoff = now()->addDays($daysAhead);

        return Employee::query()
            ->where('employees.org_id', $orgId)
            ->where('employment_status', 'active')
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereNotNull('passport_expiry')->where('passport_expiry', '<=', $cutoff))
                ->orWhere(fn ($q) => $q->whereNotNull('work_permit_expiry')->where('work_permit_expiry', '<=', $cutoff))
                ->orWhere(fn ($q) => $q->whereNotNull('residency_permit_expiry')->where('residency_permit_expiry', '<=', $cutoff))
            )
            ->select(
                'employees.id',
                'employees.first_name',
                'employees.last_name',
                'passport_expiry',
                'work_permit_expiry',
                'residency_permit_expiry',
            )
            ->orderBy('passport_expiry')
            ->get();
    }

    public function overtimeSummary(int $orgId, string $from, string $to): Collection
    {
        return OtherRequest::query()
            ->where('other_requests.org_id', $orgId)
            ->where('type', 'overtime')
            ->where('status', 'approved')
            ->whereBetween('request_date', [$from, $to])
            ->leftJoin('employees', 'employees.id', '=', 'other_requests.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->select(
                'employees.first_name',
                'employees.last_name',
                'departments.name as department',
                DB::raw('sum(other_requests.hours_requested) as total_hours'),
                DB::raw('count(*) as requests_count'),
            )
            ->groupBy('employees.id', 'employees.first_name', 'employees.last_name', 'departments.name')
            ->orderByDesc('total_hours')
            ->get();
    }

    public function teamLeaveBalance(int $managerEmployeeId): Collection
    {
        return $this->leaveBalanceSummary(0, $managerEmployeeId);
    }

    public function ownLeaveBalance(User $user): ?object
    {
        if (! $user->employee_id) {
            return null;
        }

        return Employee::query()
            ->where('id', $user->employee_id)
            ->select('id', 'first_name', 'last_name', 'annual_leave_balance_days', 'sick_leave_balance_days', 'casual_leave_balance_days')
            ->first();
    }
}
