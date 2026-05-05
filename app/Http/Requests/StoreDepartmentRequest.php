<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('org.departments.manage');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments')->where('org_id', $this->user()->org_id)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('org_id', $this->user()->org_id),
            ],
        ];
    }
}
