<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('org.holidays.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'date' => [
                'required',
                'date',
                Rule::unique('holidays', 'date')
                    ->where(fn ($q) => $q->where('org_id', $this->user()->org_id))
                    ->whereNull('deleted_at'),
            ],
            'is_recurring' => ['sometimes', 'boolean'],
            'is_make_up' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.unique' => 'A holiday already exists on this date for your organization.',
        ];
    }
}
