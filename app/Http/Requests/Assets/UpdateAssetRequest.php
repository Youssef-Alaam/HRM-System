<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assets.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $orgId = $this->user()?->org_id;
        $assetId = (int) $this->route('asset');

        return [
            'asset_category_id' => [
                'sometimes',
                Rule::exists('asset_categories', 'id')->where('org_id', $orgId),
            ],
            'name' => ['sometimes', 'string', 'max:200'],
            'serial_number' => [
                'sometimes', 'nullable', 'string', 'max:100',
                Rule::unique('assets', 'serial_number')
                    ->where('org_id', $orgId)
                    ->ignore($assetId),
            ],
            'model' => ['sometimes', 'nullable', 'string', 'max:200'],
            'value_piasters' => ['sometimes', 'integer', 'min:0'],
            'acquired_date' => ['sometimes', 'nullable', 'date'],
            'condition_at_acquisition' => [
                'sometimes',
                Rule::in(['new', 'used', 'refurbished']),
            ],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
