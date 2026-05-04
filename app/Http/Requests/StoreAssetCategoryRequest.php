<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.asset_categories.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $orgId = $this->user()?->org_id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('asset_categories', 'name')->where('org_id', $orgId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'icon_name' => ['nullable', 'string', 'max:50'],
            'order_index' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
