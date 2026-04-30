<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employees.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $orgId = $this->user()?->org_id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('employees', 'email')->where('org_id', $orgId),
            ],
            // Egyptian mobile: 11 digits, starts 010 / 011 / 012 / 015
            'phone' => ['required', 'string', 'regex:/^01[0125]\d{8}$/'],
            // Egyptian National ID: 14 digits, starts with 2 (1900s) or 3 (2000s)
            'national_id' => [
                'required',
                'string',
                'regex:/^[23]\d{13}$/',
                Rule::unique('employees', 'national_id')->where('org_id', $orgId),
            ],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1940-01-01'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'nationality' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:200'],
            'emergency_contact_phone' => ['nullable', 'string', 'regex:/^01[0125]\d{8}$/'],

            // Org assignment — exists rule scoped to user's org
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('org_id', $orgId),
            ],
            'position_id' => [
                'required',
                Rule::exists('positions', 'id')->where('org_id', $orgId),
            ],
            'office_id' => [
                'required',
                Rule::exists('offices', 'id')->where('org_id', $orgId),
            ],
            'manager_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where('org_id', $orgId)->whereNull('deleted_at'),
            ],
            'hiring_date' => ['required', 'date'],

            // Contract
            'contract_type' => [
                'required',
                Rule::in(['probation', 'fixed', 'unlimited', 'part_time', 'internship', 'project']),
            ],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],

            // Compensation (piasters per Decision 8)
            'base_salary_piasters' => ['required', 'integer', 'min:0'],

            // Expat fields — required only when is_expat is true
            'is_expat' => ['required', 'boolean'],
            'passport_number' => ['required_if:is_expat,true', 'nullable', 'string', 'max:50'],
            'passport_expiry' => ['required_if:is_expat,true', 'nullable', 'date', 'after:today'],
            'work_permit_number' => ['required_if:is_expat,true', 'nullable', 'string', 'max:50'],
            'work_permit_expiry' => ['required_if:is_expat,true', 'nullable', 'date', 'after:today'],

            // Photo (optional on create; full upload flow lands later)
            'photo' => ['nullable', 'image', 'max:4096', 'mimes:jpeg,png,webp'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'national_id.regex' => 'National ID must be 14 digits starting with 2 or 3 (Egyptian ID format).',
            'phone.regex' => 'Phone must be an 11-digit Egyptian mobile number starting with 010, 011, 012, or 015.',
            'emergency_contact_phone.regex' => 'Emergency contact phone must be an 11-digit Egyptian mobile number.',
            'passport_expiry.after' => 'Passport must not be expired at hire date.',
            'work_permit_expiry.after' => 'Work permit must not be expired at hire date.',
        ];
    }
}
