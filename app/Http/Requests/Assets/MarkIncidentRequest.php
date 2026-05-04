<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared request for /assets/{id}/mark-lost and /mark-damaged. Notes are
 * required either way — HR needs an explanation in the audit chain.
 */
class MarkIncidentRequest extends FormRequest
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
            'notes' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
