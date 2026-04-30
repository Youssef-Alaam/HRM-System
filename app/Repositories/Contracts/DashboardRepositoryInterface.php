<?php

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    /**
     * Count of active employees in the current org (OrgScope-filtered).
     */
    public function activeHeadcount(): int;

    /**
     * Active employees grouped by department, ordered by count desc.
     *
     * @return array<int, array{name: string, count: int}>
     */
    public function departmentBreakdown(): array;

    /**
     * Leave-balance row for the given employee, or null if absent.
     *
     * @return array{annual: float, sick: float, casual: float}|null
     */
    public function employeeLeaveBalance(int $employeeId): ?array;

    /**
     * Last N successful login events from audit_logs (current org only).
     *
     * @return array<int, array{name: string, email: string, at: string, ip: ?string}>
     */
    public function recentLogins(int $limit = 5): array;

    /**
     * System-wide counts for the admin dashboard.
     *
     * @return array{users: int, audit_entries: int, holidays: int}
     */
    public function systemMetrics(): array;
}
