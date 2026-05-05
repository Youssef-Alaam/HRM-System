<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * Per Labor Law Art. 17:
 *   - Foreign employees ≤ 10% of total active headcount
 *   - Foreign compensation ≤ 35% of total active payroll
 * Dashboard shows real-time compliance (Decision/ANA-3.20).
 */
class ForeignQuotaService extends BaseService
{
    private const HEADCOUNT_LIMIT = 0.10;

    private const PAYROLL_LIMIT = 0.35;

    /** @return array<string, mixed> */
    public function quotaStatus(int $orgId): array
    {
        $totals = Employee::query()
            ->where('org_id', $orgId)
            ->where('employment_status', 'active')
            ->select(
                DB::raw('count(*) as total_employees'),
                DB::raw('sum(case when is_expat = 1 then 1 else 0 end) as expat_employees'),
                DB::raw('sum(base_salary_piasters) as total_salary'),
                DB::raw('sum(case when is_expat = 1 then base_salary_piasters else 0 end) as expat_salary'),
            )
            ->first();

        $totalCount = (int) $totals->total_employees;
        $expatCount = (int) $totals->expat_employees;
        $totalSalary = (int) $totals->total_salary;
        $expatSalary = (int) $totals->expat_salary;

        $headcountRatio = $totalCount > 0 ? $expatCount / $totalCount : 0;
        $payrollRatio = $totalSalary > 0 ? $expatSalary / $totalSalary : 0;

        $headcountOk = $headcountRatio <= self::HEADCOUNT_LIMIT;
        $payrollOk = $payrollRatio <= self::PAYROLL_LIMIT;

        return [
            'total_employees' => $totalCount,
            'expat_employees' => $expatCount,
            'local_employees' => $totalCount - $expatCount,
            'headcount_ratio' => round($headcountRatio * 100, 2),
            'headcount_limit' => self::HEADCOUNT_LIMIT * 100,
            'headcount_ok' => $headcountOk,
            'total_salary_piasters' => $totalSalary,
            'expat_salary_piasters' => $expatSalary,
            'payroll_ratio' => round($payrollRatio * 100, 2),
            'payroll_limit' => self::PAYROLL_LIMIT * 100,
            'payroll_ok' => $payrollOk,
            // Overall status: green/yellow/red
            'status' => match (true) {
                ! $headcountOk || ! $payrollOk => 'red',
                $headcountRatio > (self::HEADCOUNT_LIMIT * 0.9) || $payrollRatio > (self::PAYROLL_LIMIT * 0.9) => 'yellow',
                default => 'green',
            },
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function expatList(int $orgId): array
    {
        return Employee::query()
            ->where('org_id', $orgId)
            ->where('employment_status', 'active')
            ->where('is_expat', true)
            ->with('department:id,name', 'position:id,title')
            ->orderBy('last_name')
            ->get()
            ->map(fn ($emp) => [
                'id' => $emp->id,
                'name' => $emp->first_name.' '.$emp->last_name,
                'nationality' => $emp->nationality,
                'department' => $emp->department?->name,
                'position' => $emp->position?->title,
                'work_permit_expiry' => $emp->work_permit_expiry?->format('Y-m-d'),
                'passport_expiry' => $emp->passport_expiry?->format('Y-m-d'),
                'base_salary_piasters' => $emp->base_salary_piasters,
            ])
            ->all();
    }

    /**
     * Can a new expat be hired without breaching the headcount quota?
     */
    public function canHireExpat(int $orgId): bool
    {
        $status = $this->quotaStatus($orgId);

        return $status['headcount_ok'];
    }
}
