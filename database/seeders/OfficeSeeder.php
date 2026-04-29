<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();

        Office::firstOrCreate(
            ['org_id' => $org->id, 'name' => 'Avenue Mall — Rehab'],
            [
                'address' => 'Avenue Mall, Rehab City, 5th Settlement, New Cairo',
                'latitude' => 30.0625300,
                'longitude' => 31.4895100,
                'allowed_check_in_radius_meters' => 150,
                'timezone' => 'Africa/Cairo',
                'is_active' => true,
            ],
        );
    }
}
