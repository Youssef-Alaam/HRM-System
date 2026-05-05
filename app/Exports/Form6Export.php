<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Annual Form 6 report — submitted to Egyptian Tax Authority (ETA).
 * Per Decision/ANA-4.14 — generates for year-end audit.
 *
 * Note: Tax calculations (income tax / withholding) deferred with Payroll (Feature 15).
 * This export covers: employee registration, SI contributions, annual salary totals.
 * HR uploads the generated file to ETA portal manually (Decision 21).
 */
class Form6Export implements FromCollection, WithHeadings, WithMapping
{
    private const INSURABLE_CAP_PIASTERS = 1_450_000;

    private const INSURABLE_FLOOR_PIASTERS = 230_000;

    private const EMPLOYEE_RATE = 0.11;

    private const EMPLOYER_RATE = 0.1875;

    public function __construct(
        private readonly int $orgId,
        private readonly int $year,
    ) {}

    public function collection(): Collection
    {
        return Employee::query()
            ->where('org_id', $this->orgId)
            ->withTrashed()
            ->where(function ($q) {
                // Include active + anyone who was active during the report year
                $q->whereIn('employment_status', ['active', 'on_leave', 'suspended', 'retired', 'terminated', 'deemed_resigned'])
                    ->where(function ($q2) {
                        $q2->whereNull('termination_date')
                            ->orWhereYear('termination_date', $this->year);
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'First Name',
            'Last Name',
            'National ID',
            'SI Number',
            'Hire Date',
            'Termination Date',
            'Status',
            'Annual Salary (EGP)',
            'Insurable Monthly Wage (EGP)',
            'Annual Employee SI (EGP)',
            'Annual Employer SI (EGP)',
            'Income Tax Withheld (EGP)',
            'Note',
        ];
    }

    /** @param Employee $row */
    public function map($row): array
    {
        $monthly = (int) $row->base_salary_piasters;
        $insurable = min(max($monthly, self::INSURABLE_FLOOR_PIASTERS), self::INSURABLE_CAP_PIASTERS);

        // Annual SI = 12 months × monthly contribution
        $annualEmpSi = round($insurable * self::EMPLOYEE_RATE) * 12;
        $annualEmplrSi = round($insurable * self::EMPLOYER_RATE) * 12;

        return [
            $row->employee_code,
            $row->first_name,
            $row->last_name,
            $row->national_id,
            $row->si_number ?? '',
            $row->hiring_date ? $row->hiring_date->format('d/m/Y') : '',
            $row->termination_date ? $row->termination_date->format('d/m/Y') : '',
            $row->employment_status,
            number_format(($monthly * 12) / 100, 2),
            number_format($insurable / 100, 2),
            number_format($annualEmpSi / 100, 2),
            number_format($annualEmplrSi / 100, 2),
            'See payroll module',
            'Income tax deferred — Payroll (F15)',
        ];
    }
}
