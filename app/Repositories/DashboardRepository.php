<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Aggregator repository — pulls cross-table summaries for the dashboard.
 * No CRUD here; read-only queries for display.
 *
 * Multi-tenancy: Employee + Department + AuditLog use the BelongsToOrg
 * trait (OrgScope), so every query is auto-filtered by auth user's org_id.
 * User + Holiday queries that bypass OrgScope must scope manually.
 */
class DashboardRepository implements DashboardRepositoryInterface
{
    public function activeHeadcount(): int
    {
        return Employee::query()
            ->where('employment_status', 'active')
            ->count();
    }

    public function departmentBreakdown(): array
    {
        return Department::query()
            ->leftJoin('employees', function ($join) {
                $join->on('employees.department_id', '=', 'departments.id')
                    ->where('employees.employment_status', '=', 'active')
                    ->whereNull('employees.deleted_at');
            })
            ->select('departments.name', DB::raw('COUNT(employees.id) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('count')
            ->orderBy('departments.name')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    public function employeeLeaveBalance(int $employeeId): ?array
    {
        /** @var Employee|null $employee */
        $employee = Employee::query()->find($employeeId);
        if (! $employee) {
            return null;
        }

        return [
            'annual' => (float) $employee->annual_leave_balance_days,
            'sick' => (float) $employee->sick_leave_balance_days,
            'casual' => (float) $employee->casual_leave_balance_days,
        ];
    }

    public function recentLogins(int $limit = 5): array
    {
        // AuditLog uses BelongsToOrg, so this is auto-scoped by org.
        // Successful logins write `action = 'login'` (failed = `login_failed`).
        return AuditLog::query()
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->where('audit_logs.action', 'login')
            ->orderByDesc('audit_logs.id')
            ->limit($limit)
            ->select([
                'audit_logs.created_at',
                'audit_logs.ip_address',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->get()
            ->map(fn ($row) => [
                'name' => (string) ($row->user_name ?? '—'),
                'email' => (string) ($row->user_email ?? '—'),
                'at' => $row->created_at?->toIso8601String() ?? '',
                'ip' => $row->ip_address,
            ])
            ->all();
    }

    public function systemMetrics(): array
    {
        $orgId = auth()->user()?->org_id;

        return [
            'users' => $orgId
                ? User::query()->where('org_id', $orgId)->count()
                : 0,
            'audit_entries' => AuditLog::query()->count(),
            'holidays' => $orgId
                ? Holiday::query()->where('org_id', $orgId)->count()
                : 0,
        ];
    }
}
