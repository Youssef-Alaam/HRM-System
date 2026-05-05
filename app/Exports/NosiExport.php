<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * NOSI monthly social insurance enrollment report.
 * Per Law 148/2019 — submitted to National Organization for Social Insurance.
 *
 * Employee SI contribution: 11% of insurable wage
 * Employer SI contribution: 18.75% of insurable wage
 * Insurable wage cap (2026): EGP 14,500/month = 1,450,000 piasters
 * Insurable wage floor (2026): EGP 2,300/month = 230,000 piasters
 */
class NosiExport implements FromCollection, WithHeadings, WithMapping
{
    private const INSURABLE_CAP_PIASTERS = 1_450_000;

    private const INSURABLE_FLOOR_PIASTERS = 230_000;

    private const EMPLOYEE_RATE = 0.11;

    private const EMPLOYER_RATE = 0.1875;

    public function __construct(
        private readonly int $orgId,
        private readonly string $month, // YYYY-MM
    ) {}

    public function collection(): Collection
    {
        return Employee::query()
            ->where('org_id', $this->orgId)
            ->whereIn('employment_status', ['active', 'on_leave', 'suspended'])
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
            'Base Salary (EGP)',
            'Insurable Wage (EGP)',
            'Employee Contribution (EGP)',
            'Employer Contribution (EGP)',
            'Total SI Contribution (EGP)',
            'Status',
        ];
    }

    /** @param Employee $row */
    public function map($row): array
    {
        $salary = (int) $row->base_salary_piasters;
        $insurable = min(max($salary, self::INSURABLE_FLOOR_PIASTERS), self::INSURABLE_CAP_PIASTERS);
        $empContrib = round($insurable * self::EMPLOYEE_RATE);
        $emplrContrib = round($insurable * self::EMPLOYER_RATE);

        return [
            $row->employee_code,
            $row->first_name,
            $row->last_name,
            $row->national_id,
            $row->si_number ?? '',
            $row->hiring_date ? $row->hiring_date->format('d/m/Y') : '',
            number_format($salary / 100, 2),
            number_format($insurable / 100, 2),
            number_format($empContrib / 100, 2),
            number_format($emplrContrib / 100, 2),
            number_format(($empContrib + $emplrContrib) / 100, 2),
            $row->employment_status,
        ];
    }
}
