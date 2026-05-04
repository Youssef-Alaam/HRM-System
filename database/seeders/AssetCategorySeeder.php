<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * Starter category list locked 2026-04-30 with Walid: Laptop /
 * Accessories / Phone / Badge ID. HR adds more from Settings as the
 * inventory grows (e.g. helmets later).
 */
class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();

        $categories = [
            ['name' => 'Laptop', 'icon_name' => 'laptop', 'description' => 'Company-issued laptop.'],
            ['name' => 'Accessories', 'icon_name' => 'mouse', 'description' => 'Mouse, keyboard, headset, monitor.'],
            ['name' => 'Phone', 'icon_name' => 'smartphone', 'description' => 'Company-issued mobile phone.'],
            ['name' => 'Badge ID', 'icon_name' => 'id-card', 'description' => 'Physical access badge or ID card.'],
        ];

        foreach ($categories as $i => $category) {
            AssetCategory::firstOrCreate(
                ['org_id' => $org->id, 'name' => $category['name']],
                array_merge($category, [
                    'order_index' => $i,
                    'is_active' => true,
                ]),
            );
        }
    }
}
