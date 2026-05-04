<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetCategoryRequest extends FormRequest
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
        $catId = (int) $this->route('category');

        return [
            'name' => [
                'sometimes', 'string', 'max:100',
                Rule::unique('asset_categories', 'name')
                    ->where('org_id', $orgId)
                    ->ignore($catId),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'icon_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
