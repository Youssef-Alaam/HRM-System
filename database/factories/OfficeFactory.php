<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Office>
 */
class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        return [
            'org_id' => Organization::factory(),
            'name' => fake()->company().' Office',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(30.0, 30.1),
            'longitude' => fake()->longitude(31.2, 31.5),
            'allowed_check_in_radius_meters' => 150,
            'timezone' => 'Africa/Cairo',
            'is_active' => true,
            'version' => 1,
        ];
    }
}
