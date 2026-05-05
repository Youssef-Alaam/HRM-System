<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectOtherRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('requests.approve.team') || $this->user()->can('requests.approve.final');
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
