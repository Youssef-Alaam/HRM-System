<?php

namespace App\Exports;

use App\Models\EmployeeDocument;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeDocumentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly int $orgId) {}

    public function collection(): Collection
    {
        return EmployeeDocument::query()
            ->where('org_id', $this->orgId)
            ->with(['employee', 'documentType', 'uploader'])
            ->orderBy('employee_id')
            ->orderBy('document_type_id')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
            'Document Type',
            'File Name',
            'Issued Date',
            'Expiry Date',
            'Status',
            'Uploaded By',
            'Uploaded At',
            'Notes',
        ];
    }

    /**
     * @param  EmployeeDocument  $row
     * @return array<int, string|null>
     */
    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee
                ? trim($row->employee->first_name.' '.$row->employee->last_name)
                : null,
            $row->documentType?->name,
            $row->original_filename,
            optional($row->issued_date)->toDateString(),
            optional($row->expiry_date)->toDateString(),
            $this->status($row),
            $row->uploader?->name,
            optional($row->uploaded_at)->toIso8601String(),
            $row->notes,
        ];
    }

    private function status(EmployeeDocument $doc): string
    {
        if ($doc->isExpired()) {
            return 'expired';
        }
        if ($doc->isExpiringWithin(30, CarbonImmutable::now())) {
            return 'expiring_soon';
        }

        return 'valid';
    }
}
