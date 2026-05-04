<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.document_types.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $orgId = $this->user()?->org_id;

        return [
            'name' => [
                'required', 'string', 'max:200',
                Rule::unique('document_types', 'name')->where('org_id', $orgId),
            ],
            'name_ar' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'applies_to' => [
                'required',
                Rule::in([
                    DocumentType::APPLIES_ALL,
                    DocumentType::APPLIES_EGYPTIAN,
                    DocumentType::APPLIES_EXPAT,
                    DocumentType::APPLIES_EGYPTIAN_MALE,
                ]),
            ],
            'is_required' => ['required', 'boolean'],
            'default_expiry_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
