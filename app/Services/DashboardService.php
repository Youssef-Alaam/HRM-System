<?php

namespace App\Services;

use App\Http\Controllers\DocumentsController;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\DocumentTypeRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * Composes the dashboard payload per role. Widget keys are present only
 * when the user's permission set entitles them — frontend simply renders
 * whatever it receives, no role-checking on the client.
 */
class DashboardService extends BaseService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $repo,
        private readonly DocumentTypeRepositoryInterface $documentTypes,
        private readonly EmployeeDocumentRepositoryInterface $documents,
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

        // Documents compliance — HR/Admin compliance counter (Feature 11).
        if ($user->can('documents.view.any')) {
            $widgets['documents_compliance'] = DocumentsController::complianceSnapshot(
                (int) $user->org_id,
                $this->documentTypes,
                $this->documents,
            );
        }

        // Face enrollment status — 30 days before / at-or-past 12-month
        // anniversary, plus forced re-enrollments via 5-failed-checkin
        // trigger. HR/Admin only (face.view.any) per the locked spec.
        if ($user->can('face.view.any')) {
            $widgets['face_enrollment_status'] = $this->faceEnrollmentSnapshot((int) $user->org_id);
        }

        // Admin-only: system metrics + recent logins. Gated on audit.view
        // which only Admin has by default per RoleDefinitions.
        if ($user->can('audit.view')) {
            $widgets['recent_logins'] = $this->repo->recentLogins(5);
            $widgets['system_metrics'] = $this->repo->systemMetrics();
        }

        return $widgets;
    }

    /**
     * Snapshot of where each active employee sits on the 12-month
     * re-enrollment cadence. "Due this month" = enrolled 11+ months ago;
     * "overdue" = past 12 months OR forced via the 5-failed trigger;
     * "never enrolled" = no descriptor on file at all.
     *
     * @return array<string, int>
     */
    private function faceEnrollmentSnapshot(int $orgId): array
    {
        $now = CarbonImmutable::now();
        $cadenceMonths = FaceEnrollmentService::REENROLLMENT_INTERVAL_MONTHS;
        $dueWindowStart = $now->subMonths($cadenceMonths - 1); // 11 months ago
        $overdueCutoff = $now->subMonths($cadenceMonths);      // 12 months ago

        $employees = Employee::query()
            ->where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->where('employment_status', 'active')
            ->get([
                'id', 'face_descriptor', 'last_face_enrollment_at',
                'requires_face_reenrollment',
            ]);

        $dueThisMonth = 0;
        $overdue = 0;
        $neverEnrolled = 0;
        $forcedReenrollment = 0;

        foreach ($employees as $employee) {
            if ($employee->requires_face_reenrollment) {
                $forcedReenrollment++;
            }
            if ($employee->face_descriptor === null) {
                $neverEnrolled++;

                continue;
            }
            $enrolledAt = $employee->last_face_enrollment_at;
            if ($enrolledAt === null) {
                continue;
            }
            if ($enrolledAt->lt($overdueCutoff)) {
                $overdue++;
            } elseif ($enrolledAt->lt($dueWindowStart)) {
                $dueThisMonth++;
            }
        }

        return [
            'due_this_month' => $dueThisMonth,
            'overdue' => $overdue,
            'never_enrolled' => $neverEnrolled,
            'forced_reenrollment' => $forcedReenrollment,
        ];
    }
}
