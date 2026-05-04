<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets.assign') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $orgId = $this->user()?->org_id;

        return [
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            // age_at_assignment_months removed 2026-04-30 — auto-computed
            // by the service from the asset's acquired_date.
            'condition_at_assignment' => [
                'required',
                Rule::in(['new', 'used', 'refurbished']),
            ],
            'expected_return_at' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
