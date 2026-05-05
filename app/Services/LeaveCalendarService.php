<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\LeaveCalendarRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class LeaveCalendarService extends BaseService
{
    private const TYPE_COLORS = [
        'annual' => 'gold', 'sick' => 'slate', 'casual' => 'slate',
        'maternity' => 'teal', 'paternity' => 'teal', 'study' => 'amber',
    ];

    public function __construct(private readonly LeaveCalendarRepositoryInterface $repo) {}

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getData(User $user, array $filters): array
    {
        $view = ($filters['view'] ?? '') === 'week' ? 'week' : 'month';
        $role = $user->can('leave.view.any') ? 'hr' : ($user->can('leave.view.team') ? 'manager' : 'employee');

        [$from, $to, $days, $meta] = $view === 'week' ? $this->buildWeek($filters) : $this->buildMonth($filters);

        $employeeIds = $this->employeeIds($user, $role, $filters);
        $raw = $this->repo->entriesForPeriod((int) $user->org_id, $from, $to, $employeeIds);

        $myEmpId = $user->employee_id ? (int) $user->employee_id : null;
        $teamIds = ($role === 'manager' && $user->employee_id)
            ? $this->repo->teamEmployeeIds((int) $user->employee_id) : [];

        $entries = $raw->map(fn ($r) => $this->fmt($r, $role, $myEmpId, $teamIds))->values();
        $holidays = $this->repo->holidaysForPeriod((int) $user->org_id, $from, $to)
            ->map(fn ($h) => ['date' => Carbon::parse($h->date)->format('Y-m-d'), 'name' => $h->name])
            ->values();

        return array_merge(['view' => $view, 'days' => $days, 'entries' => $entries, 'holidays' => $holidays, 'role' => $role], $meta);
    }

    /** @param array<string, mixed> $filters
     * @return array<int>
     */
    private function employeeIds(User $user, string $role, array $filters): array
    {
        if ($role === 'hr') {
            if (! empty($filters['department_id'])) {
                return Employee::withoutGlobalScopes()
                    ->where('org_id', (int) $user->org_id)
                    ->where('department_id', (int) $filters['department_id'])
                    ->whereNull('deleted_at')->pluck('id')->all();
            }

            return $this->repo->allEmployeeIds((int) $user->org_id);
        }

        if ($role === 'manager' && $user->employee_id) {
            return array_merge([(int) $user->employee_id], $this->repo->teamEmployeeIds((int) $user->employee_id));
        }

        return [];
    }

    /** @param array<string, mixed> $filters */
    private function buildMonth(array $filters): array
    {
        $year = max(2000, min(2100, (int) ($filters['year'] ?? now()->year)));
        $month = max(1, min(12, (int) ($filters['month'] ?? now()->month)));
        $first = CarbonImmutable::create($year, $month, 1);
        $last = $first->endOfMonth();
        [$from, $to] = [$first->format('Y-m-d'), $last->format('Y-m-d')];

        $days = [];
        for ($cur = $first; $cur->lte($last); $cur = $cur->addDay()) {
            $days[] = ['date' => $cur->format('Y-m-d'), 'is_today' => $cur->isToday(),
                'is_weekend' => in_array($cur->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true)];
        }

        return [$from, $to, $days, ['year' => $year, 'month' => $month, 'week' => null, 'week_start' => $from, 'week_end' => $to]];
    }

    /** @param array<string, mixed> $filters */
    private function buildWeek(array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $week = (int) ($filters['week'] ?? now()->weekOfYear);
        $monday = CarbonImmutable::now()->setISODate($year, $week)->startOfDay();
        $sunday = $monday->addDays(6);
        [$from, $to] = [$monday->format('Y-m-d'), $sunday->format('Y-m-d')];

        $days = [];
        for ($cur = $monday; $cur->lte($sunday); $cur = $cur->addDay()) {
            $days[] = ['date' => $cur->format('Y-m-d'), 'is_today' => $cur->isToday(),
                'is_weekend' => in_array($cur->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true)];
        }

        return [$from, $to, $days, ['year' => $year, 'month' => $monday->month, 'week' => $week, 'week_start' => $from, 'week_end' => $to]];
    }

    /** @param array<int> $teamIds
     * @return array<string, mixed>
     */
    private function fmt(mixed $req, string $role, ?int $myEmpId, array $teamIds): array
    {
        $empId = (int) $req->employee_id;
        $show = $role === 'hr' || $empId === $myEmpId || ($role === 'manager' && in_array($empId, $teamIds, true));

        return [
            'id' => $req->id,
            'employee_id' => $empId,
            'employee_name' => $req->employee ? $req->employee->first_name.' '.$req->employee->last_name : '–',
            'leave_type_code' => $show ? $req->leaveType?->code : null,
            'leave_type_name' => $show ? $req->leaveType?->name : null,
            'leave_type_color' => self::TYPE_COLORS[$req->leaveType?->code ?? ''] ?? 'default',
            'start_date' => Carbon::parse($req->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($req->end_date)->format('Y-m-d'),
            'days_count' => (int) $req->days_count,
        ];
    }
}
