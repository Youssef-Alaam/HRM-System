<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leave.approve.team') || $this->user()->can('leave.approve.final');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rejected_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
