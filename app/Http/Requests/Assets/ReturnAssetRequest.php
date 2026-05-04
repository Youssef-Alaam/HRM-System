<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets.assign') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'return_condition' => [
                'required',
                Rule::in(['good', 'damaged', 'lost']),
            ],
            'return_notes' => [
                // damaged/lost require explanatory notes per spec.
                'required_unless:return_condition,good',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
