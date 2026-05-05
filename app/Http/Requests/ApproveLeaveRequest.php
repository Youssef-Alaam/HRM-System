<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leave.approve.team') || $this->user()->can('leave.approve.final');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
