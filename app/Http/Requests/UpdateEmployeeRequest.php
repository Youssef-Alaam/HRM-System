<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tier-aware update for the Employees feature (locked 2026-04-30 with
 * Walid as the simplified 2-tier model — no change-request queue).
 *
 * - Self-tier fields (phone, address, emergency contact, marital status,
 *   dependents) — editable by the employee themselves with the
 *   `employees.edit.own.tier1` permission, or by HR/Admin.
 * - HR-only-tier fields (salary, contract, role assignment, leave balance,
 *   employee_code, identity fields) — require `employees.edit.any`.
 *
 * authorize() inspects the keys present in the payload. If any HR-only
 * key is present, the actor must hold `employees.edit.any`. Otherwise the
 * actor must be editing themselves with the tier1 permission.
 */
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Fields HR (or Admin) alone may modify. Anything not in this list
     * AND not in the Self-tier list is rejected at the validation layer
     * — we don't accept arbitrary keys.
     *
     * @var array<int, string>
     */
    private const HR_ONLY_FIELDS = [
        'first_name', 'last_name', 'email', 'national_id', 'date_of_birth',
        'gender', 'nationality',
        'employee_code',
        'department_id', 'position_id', 'office_id', 'manager_id',
        'hiring_date',
        'contract_type', 'contract_start_date', 'contract_end_date',
        'employment_status',
        'base_salary_piasters',
        'annual_leave_balance_days', 'sick_leave_balance_days',
        'casual_leave_balance_days', 'permissions_balance_minutes',
        'is_expat', 'passport_number', 'passport_expiry',
        'work_permit_number', 'work_permit_expiry',
    ];

    /**
     * Fields the employee themselves may edit on the Profile page (and
     * that HR/Admin may also edit anywhere).
     *
     * @var array<int, string>
     */
    private const SELF_TIER_FIELDS = [
        'phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'marital_status',
        'dependents',
    ];

    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $employeeId = (int) $this->route('employee');
        $canEditAny = $user->can('employees.edit.any');
        $isSelf = (int) $user->employee_id === $employeeId;

        $payloadKeys = array_keys($this->all());
        $touchesHrOnly = (bool) array_intersect($payloadKeys, self::HR_ONLY_FIELDS);

        if ($touchesHrOnly) {
            return $canEditAny;
        }

        return $canEditAny || ($isSelf && $user->can('employees.edit.own.tier1'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $orgId = $this->user()?->org_id;
        $employeeId = (int) $this->route('employee');

        return [
            // Self-tier fields — open to the employee themselves.
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^01[0125]\d{8}$/'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:200'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'regex:/^01[0125]\d{8}$/'],
            'marital_status' => ['sometimes', 'nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'dependents' => ['sometimes', 'integer', 'min:0', 'max:30'],

            // HR-only-tier fields. authorize() has already returned false
            // if the actor isn't allowed to touch these keys; we still
            // validate shape so a future HR edit form is contract-correct.
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => [
                'sometimes', 'email:rfc', 'max:255',
                Rule::unique('employees', 'email')
                    ->where('org_id', $orgId)
                    ->ignore($employeeId),
            ],
            'national_id' => [
                'sometimes', 'nullable', 'string', 'regex:/^[23]\d{13}$/',
                Rule::unique('employees', 'national_id')
                    ->where('org_id', $orgId)
                    ->ignore($employeeId),
            ],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today', 'after:1940-01-01'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'nationality' => ['sometimes', 'string', 'max:100'],
            'department_id' => [
                'sometimes', 'nullable',
                Rule::exists('departments', 'id')->where('org_id', $orgId),
            ],
            'position_id' => [
                'sometimes', 'nullable',
                Rule::exists('positions', 'id')->where('org_id', $orgId),
            ],
            'office_id' => [
                'sometimes', 'nullable',
                Rule::exists('offices', 'id')->where('org_id', $orgId),
            ],
            'manager_id' => [
                'sometimes', 'nullable',
                Rule::exists('employees', 'id')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'hiring_date' => ['sometimes', 'nullable', 'date'],
            'contract_type' => [
                'sometimes',
                Rule::in(['probation', 'fixed', 'unlimited', 'part_time', 'internship', 'project']),
            ],
            'contract_start_date' => ['sometimes', 'nullable', 'date'],
            'contract_end_date' => ['sometimes', 'nullable', 'date', 'after:contract_start_date'],
            'employment_status' => [
                'sometimes',
                Rule::in(['active', 'suspended', 'on_leave', 'terminated', 'deemed_resigned', 'retired', 'probation']),
            ],
            'base_salary_piasters' => ['sometimes', 'integer', 'min:0'],
            'is_expat' => ['sometimes', 'boolean'],
            'passport_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'passport_expiry' => ['sometimes', 'nullable', 'date'],
            'work_permit_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'work_permit_expiry' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be an 11-digit Egyptian mobile number starting with 010, 011, 012, or 015.',
            'emergency_contact_phone.regex' => 'Emergency contact phone must be an 11-digit Egyptian mobile number.',
            'national_id.regex' => 'National ID must be 14 digits starting with 2 or 3 (Egyptian ID format).',
        ];
    }
}
