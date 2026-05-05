<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TerminateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.terminate');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in([
                'resignation',
                'dismissal',
                'deemed_resignation',
                'retirement',
                'end_of_contract',
                'mutual_agreement',
                'probation_termination',
            ])],
            'effective_date' => ['nullable', 'date', 'after_or_equal:today'],
            'override_protection' => ['boolean'],
        ];
    }
}
