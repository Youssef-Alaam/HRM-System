<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly int $orgId) {}

    public function collection(): Collection
    {
        return Employee::query()
            ->where('org_id', $this->orgId)
            ->with(['department', 'position', 'office', 'manager'])
            ->orderBy('employee_code')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Employee Code',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'National ID',
            'Department',
            'Position',
            'Office',
            'Manager',
            'Hiring Date',
            'Employment Status',
            'Is Expat',
            'Base Salary (EGP)',
        ];
    }

    /**
     * @param  Employee  $row
     * @return array<int, string|int|null>
     */
    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->first_name,
            $row->last_name,
            $row->email,
            $row->phone,
            $row->national_id,
            $row->department?->name,
            $row->position?->title,
            $row->office?->name,
            $row->manager ? trim($row->manager->first_name.' '.$row->manager->last_name) : null,
            optional($row->hiring_date)->toDateString(),
            $row->employment_status,
            $row->is_expat ? 'Yes' : 'No',
            number_format(((int) $row->base_salary_piasters) / 100, 2, '.', ','),
        ];
    }
}
