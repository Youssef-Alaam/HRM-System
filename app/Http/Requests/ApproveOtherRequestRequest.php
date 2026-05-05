<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveOtherRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('requests.approve.team') || $this->user()->can('requests.approve.final');
    }

    public function rules(): array
    {
        return [];
    }
}
