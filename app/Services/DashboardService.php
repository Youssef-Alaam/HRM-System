<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\DashboardRepositoryInterface;

/**
 * Composes the dashboard payload per role. Widget keys are present only
 * when the user's permission set entitles them — frontend simply renders
 * whatever it receives, no role-checking on the client.
 */
class DashboardService extends BaseService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $repo,
    ) {}

    /**
     * @return array<string, mixed> Dashboard widget payload, role-filtered.
     */
    public function getDataForRole(User $user): array
    {
        $widgets = [
            'identity' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->first() ?? 'employee',
            ],
        ];

        // Personal — present whenever the user is linked to an Employee.
        if ($user->employee_id) {
            $balance = $this->repo->employeeLeaveBalance($user->employee_id);
            if ($balance) {
                $widgets['leave_balance'] = $balance;
            }
        }

        // Team-and-up: anyone who can view team data sees the headcount.
        if ($user->can('employees.view.team') || $user->can('employees.view.any')) {
            $widgets['headcount'] = [
                'total' => $this->repo->activeHeadcount(),
            ];
        }

        // HR-and-up: department breakdown.
        if ($user->can('employees.view.any')) {
            $widgets['department_breakdown'] = $this->repo->departmentBreakdown();
        }

        // Admin-only: system metrics + recent logins. Gated on audit.view
        // which only Admin has by default per RoleDefinitions.
        if ($user->can('audit.view')) {
            $widgets['recent_logins'] = $this->repo->recentLogins(5);
            $widgets['system_metrics'] = $this->repo->systemMetrics();
        }

        return $widgets;
    }
}
