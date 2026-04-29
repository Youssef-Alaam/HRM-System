<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::firstOrCreate(
            ['name' => 'YZH Solutions'],
            [
                'legal_name' => 'YZH Solutions LLC',
                'country' => 'EG',
                'currency' => 'EGP',
                'timezone' => 'Africa/Cairo',
                'address' => 'Avenue Mall, Rehab City, New Cairo, Egypt',
                'phone' => '+20 2 1234 5678',
            ],
        );
    }
}
