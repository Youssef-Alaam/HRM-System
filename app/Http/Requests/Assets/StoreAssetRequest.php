<?php

namespace App\Http\Requests\Assets;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
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

        return [
            'asset_category_id' => [
                'required',
                Rule::exists('asset_categories', 'id')
                    ->where('org_id', $orgId)
                    ->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:200'],
            'serial_number' => [
                'nullable', 'string', 'max:100',
                Rule::unique('assets', 'serial_number')->where('org_id', $orgId),
            ],
            'model' => ['nullable', 'string', 'max:200'],
            'value_piasters' => ['required', 'integer', 'min:0'],
            'acquired_date' => ['nullable', 'date', 'before_or_equal:today'],
            'condition_at_acquisition' => [
                'required',
                Rule::in(['new', 'used', 'refurbished']),
            ],
            'current_status' => [
                'sometimes',
                Rule::in([
                    Asset::STATUS_IN_POOL,
                    Asset::STATUS_LOST,
                    Asset::STATUS_DAMAGED,
                    Asset::STATUS_WRITTEN_OFF,
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
