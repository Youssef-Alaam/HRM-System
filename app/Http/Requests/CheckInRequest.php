<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attendance.checkin.own') && $this->user()->employee_id !== null;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'verdict_score' => ['required', 'numeric', 'between:0,1'],
            'selfie_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
