<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ComplianceService extends BaseService
{
    private const PROBATION_MAX_DAYS = 90;

    /**
     * Employees on probation contract with days-remaining, grouped by urgency.
     */
    public function probationStatus(int $orgId): Collection
    {
        return Employee::query()
            ->where('org_id', $orgId)
            ->where('employment_status', 'active')
            ->where('contract_type', 'probation')
            ->whereNotNull('contract_start_date')
            ->with('department:id,name', 'position:id,title')
            ->orderBy('contract_start_date')
            ->get()
            ->map(function (Employee $emp) {
                $probationEnds = Carbon::parse($emp->contract_start_date)->addDays(self::PROBATION_MAX_DAYS);
                $daysRemaining = (int) now()->diffInDays($probationEnds, false);

                return [
                    'id' => $emp->id,
                    'name' => $emp->first_name.' '.$emp->last_name,
                    'department' => $emp->department?->name,
                    'position' => $emp->position?->title,
                    'contract_start_date' => $emp->contract_start_date->format('Y-m-d'),
                    'probation_ends' => $probationEnds->format('Y-m-d'),
                    'days_remaining' => $daysRemaining,
                    // Per Art. 41: Day 75 = 15-day warning; Day 90 = auto-convert
                    'alert_level' => match (true) {
                        $daysRemaining <= 0 => 'overdue',
                        $daysRemaining <= 15 => 'warning',
                        default => 'ok',
                    },
                ];
            });
    }

    /**
     * Employees approaching retirement age (within 6 months of 60th birthday).
     * Per Social Insurance Law 148/2019 §11.
     */
    public function retirementAlerts(int $orgId): Collection
    {
        $sixMonthsAhead = now()->addMonths(6);

        return Employee::query()
            ->where('org_id', $orgId)
            ->whereIn('employment_status', ['active', 'on_leave', 'suspended'])
            ->whereNotNull('date_of_birth')
            ->whereNull('retirement_extended_until')
            ->get()
            ->filter(function (Employee $emp) use ($sixMonthsAhead) {
                // 60th birthday
                $retirement = Carbon::parse($emp->date_of_birth)->addYears(60);

                return $retirement->isFuture() && $retirement->lte($sixMonthsAhead);
            })
            ->map(function (Employee $emp) {
                $retirement = Carbon::parse($emp->date_of_birth)->addYears(60);

                return [
                    'id' => $emp->id,
                    'name' => $emp->first_name.' '.$emp->last_name,
                    'date_of_birth' => $emp->date_of_birth->format('Y-m-d'),
                    'retirement_date' => $retirement->format('Y-m-d'),
                    'days_until_retirement' => (int) now()->diffInDays($retirement, false),
                ];
            })
            ->values();
    }

    /**
     * Employees currently flagged for deemed resignation (20+ unauthorized absences YTD).
     * 10-consecutive-day detection requires attendance data (Feature 5 — deferred).
     * Per Labor Law Art. 69.
     */
    public function deemedResignationCandidates(int $orgId): Collection
    {
        return Employee::query()
            ->where('org_id', $orgId)
            ->where('employment_status', 'active')
            ->where('unauthorized_absences_ytd', '>=', 20)
            ->with('department:id,name')
            ->orderByDesc('unauthorized_absences_ytd')
            ->get()
            ->map(fn (Employee $emp) => [
                'id' => $emp->id,
                'name' => $emp->first_name.' '.$emp->last_name,
                'department' => $emp->department?->name,
                'unauthorized_absences_ytd' => $emp->unauthorized_absences_ytd,
            ]);
    }

    /**
     * Calculate EOSB in piasters per Labor Law Art. 110-115.
     * Based on last drawn salary (base_salary_piasters) × years of service.
     */
    public function calculateEosb(Employee $employee): int
    {
        if (! $employee->hiring_date) {
            return 0;
        }

        // Carbon 3: call diffInYears on the past date toward now for positive result
        $yearsOfService = (float) Carbon::parse($employee->hiring_date)->diffInYears(now());

        if ($yearsOfService <= 0) {
            return 0;
        }

        $monthSalary = $employee->base_salary_piasters;

        if ($employee->contract_type === 'probation') {
            // Per Art. 41: no severance during probation
            return 0;
        }

        // Per Art. 110-115 — indefinite/unlimited contracts:
        // First 5 years: half month per year; above 5 years: full month per additional year
        if ($yearsOfService <= 5) {
            return (int) round($monthSalary * 0.5 * $yearsOfService);
        }

        $first5 = (int) round($monthSalary * 0.5 * 5);
        $remaining = (int) round($monthSalary * ($yearsOfService - 5));

        return $first5 + $remaining;
    }

    /**
     * Calculate notice period in days per Labor Law Art. 110-115.
     */
    public function noticePeriodDays(Employee $employee): int
    {
        return match ($employee->contract_type) {
            'probation' => 0,   // Per Art. 41: no notice during probation
            'fixed' => 30,      // 1 month notice for fixed-term
            default => 90,      // Per Art. 110: 3 months for indefinite/unlimited
        };
    }

    /**
     * Terminate an employee with full compliance checks.
     * Per Labor Law Art. 110-115 + Art. 93 (maternity protection).
     */
    public function terminate(Employee $employee, User $actor, array $data): Employee
    {
        // Protection check — cannot terminate during maternity leave
        if ($employee->employment_status === 'on_leave') {
            // Simplified: block all on-leave terminations unless forced by HR with reason
            if (! ($data['override_protection'] ?? false)) {
                throw ValidationException::withMessages([
                    'override_protection' => 'Per Labor Law Art. 93, termination during leave is protected. Set override_protection = true to proceed with documented justification.',
                ]);
            }
        }

        $eosb = $this->calculateEosb($employee);
        $noticeDays = $this->noticePeriodDays($employee);
        $noticeDate = now()->format('Y-m-d');
        $effectiveDate = $data['effective_date'] ?? now()->addDays($noticeDays)->format('Y-m-d');

        return $this->transaction(function () use ($employee, $data, $eosb, $noticeDate, $effectiveDate) {
            $employee->update([
                'employment_status' => match ($data['reason'] ?? '') {
                    'retirement' => 'retired',
                    'deemed_resignation' => 'deemed_resigned',
                    default => 'terminated',
                },
                'termination_date' => $effectiveDate,
                'termination_reason' => $data['reason'] ?? 'termination',
                'termination_notice_date' => $noticeDate,
                'eosb_piasters' => $eosb,
            ]);

            return $employee->refresh();
        });
    }

    /**
     * Extend retirement for an employee past their 60th birthday.
     * Per Social Insurance Law 148/2019 §11.
     */
    public function extendRetirement(Employee $employee, string $until): Employee
    {
        return $this->transaction(function () use ($employee, $until) {
            $employee->update(['retirement_extended_until' => $until]);

            return $employee->refresh();
        });
    }
}
