<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentTypeRequest extends FormRequest
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
        $typeId = (int) $this->route('type');

        return [
            'name' => [
                'sometimes', 'string', 'max:200',
                Rule::unique('document_types', 'name')
                    ->where('org_id', $orgId)
                    ->ignore($typeId),
            ],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'applies_to' => [
                'sometimes',
                Rule::in([
                    DocumentType::APPLIES_ALL,
                    DocumentType::APPLIES_EGYPTIAN,
                    DocumentType::APPLIES_EXPAT,
                    DocumentType::APPLIES_EGYPTIAN_MALE,
                ]),
            ],
            'is_required' => ['sometimes', 'boolean'],
            'default_expiry_months' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
