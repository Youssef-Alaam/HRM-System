<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for an HR document upload (Feature 11). 10 MB cap and
 * MIME allow-list locked 2026-04-30 with Walid.
 */
class StoreEmployeeDocumentRequest extends FormRequest
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
        $orgId = $this->user()?->org_id;

        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:pdf,jpeg,jpg,png,webp',
            ],
            'document_type_id' => [
                'nullable',
                Rule::exists('document_types', 'id')
                    ->where('org_id', $orgId)
                    ->where('is_active', true),
            ],
            'issued_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'File must be 10 MB or smaller.',
            'file.mimes' => 'File must be PDF, JPEG, PNG, or WebP.',
            'expiry_date.after' => 'Expiry date must be in the future.',
        ];
    }

    /**
     * Auto-fill the expiry date from the document type's
     * `default_expiry_months` if the form left it blank.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('document_type_id') && ! $this->filled('expiry_date')) {
            $type = DocumentType::query()->find($this->input('document_type_id'));
            if ($type && $type->default_expiry_months) {
                $this->merge([
                    'expiry_date' => now()->addMonths($type->default_expiry_months)->toDateString(),
                ]);
            }
        }
    }
}
