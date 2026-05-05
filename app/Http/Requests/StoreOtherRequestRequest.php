<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOtherRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('requests.create.own') && $this->user()->employee_id !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['overtime', 'expense_claim', 'change_shift', 'holiday_work'])],
            'request_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            // Per Labor Law Art. 85 — overtime max 2 hrs/day
            'hours_requested' => [
                Rule::requiredIf($this->input('type') === 'overtime'),
                'nullable',
                'numeric',
                'min:0.25',
                'max:2',
            ],
            'amount_piasters' => [
                Rule::requiredIf($this->input('type') === 'expense_claim'),
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
