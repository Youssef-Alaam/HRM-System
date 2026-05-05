<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('announcements.create');
    }

    public function rules(): array
    {
        $orgId = (int) $this->user()->org_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'target_type' => ['required', Rule::in(['all', 'department'])],
            'target_id' => [
                'nullable',
                'integer',
                Rule::requiredIf($this->input('target_type') === 'department'),
                Rule::exists('departments', 'id')->where('org_id', $orgId),
            ],
            'pinned' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
