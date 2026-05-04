<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Metadata patch for an existing employee document (issued/expiry/notes).
 * Replacement uploads go through StoreEmployeeDocumentRequest with the
 * same (employee, type) pair — the service handles the soft-delete swap.
 */
class UpdateEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('documents.upload') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'issued_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['sometimes', 'nullable', 'date', 'after:today'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
