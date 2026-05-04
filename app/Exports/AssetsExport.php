<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly int $orgId) {}

    public function collection(): Collection
    {
        return Asset::query()
            ->where('org_id', $this->orgId)
            ->with(['category', 'currentEmployee'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Asset ID',
            'Category',
            'Name / Model',
            'Serial Number',
            'Status',
            'Current Holder',
            'Holder Code',
            'Acquired Date',
            'Condition at Acquisition',
            'Value (EGP)',
            'Notes',
        ];
    }

    /**
     * @param  Asset  $row
     * @return array<int, string|int|null>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->category?->name,
            $row->name,
            $row->serial_number,
            $row->current_status,
            $row->currentEmployee
                ? trim($row->currentEmployee->first_name.' '.$row->currentEmployee->last_name)
                : null,
            $row->currentEmployee?->employee_code,
            optional($row->acquired_date)->toDateString(),
            $row->condition_at_acquisition,
            number_format(((int) $row->value_piasters) / 100, 2, '.', ','),
            $row->notes,
        ];
    }
}
