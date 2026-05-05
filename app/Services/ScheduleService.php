<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Carbon\Carbon;

class ScheduleService extends BaseService
{
    private const DEFAULT_WORKWEEK = ['sun', 'mon', 'tue', 'wed', 'thu'];

    private const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    public function __construct(private readonly ScheduleRepositoryInterface $repo) {}

    /**
     * @return array<string, mixed>
     */
    public function getWeekData(User $user, ?string $weekParam): array
    {
        $weekStart = $this->resolveWeekStart($weekParam);
        $weekEnd = $weekStart->copy()->addDays(6);

        $employee = $this->repo->findEmployeeForUser($user->id);
        $workweekDays = $employee?->workweek_days ?? self::DEFAULT_WORKWEEK;

        $holidays = $this->repo->getHolidaysForRange(
            (int) $user->org_id,
            $weekStart->format('Y-m-d'),
            $weekEnd->format('Y-m-d'),
        )->keyBy(fn ($h) => Carbon::parse($h->date)->format('Y-m-d'));

        $today = Carbon::today()->format('Y-m-d');
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->copy()->addDays($i);
            $date = $day->format('Y-m-d');
            $name = self::DAY_NAMES[$day->dayOfWeek];
            $hol = $holidays->get($date);

            $days[] = [
                'date' => $date,
                'day_name' => $name,
                'is_workday' => in_array(strtolower($name), $workweekDays, true),
                'is_today' => $date === $today,
                'holiday' => $hol ? ['id' => $hol->id, 'name' => $hol->name] : null,
            ];
        }

        $employeeData = null;
        if ($employee) {
            $employeeData = [
                'shift_start' => $employee->shift_start_time?->format('H:i'),
                'shift_end' => $employee->shift_end_time?->format('H:i'),
                'workweek_days' => $workweekDays,
            ];
        }

        return [
            'employee' => $employeeData,
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'days' => $days,
            'prev_week' => $weekStart->copy()->subDays(7)->format('Y-m-d'),
            'next_week' => $weekStart->copy()->addDays(7)->format('Y-m-d'),
        ];
    }

    private function resolveWeekStart(?string $weekParam): Carbon
    {
        if ($weekParam) {
            try {
                $parsed = Carbon::createFromFormat('Y-m-d', $weekParam);
                if ($parsed && $parsed->format('Y-m-d') === $weekParam) {
                    // Snap to the Sunday of that week
                    return $parsed->startOfWeek(Carbon::SUNDAY);
                }
            } catch (\Exception) {
                // Fall through to default
            }
        }

        return Carbon::today()->startOfWeek(Carbon::SUNDAY);
    }
}
