<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company().' LLC',
            'country' => 'EG',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'address' => $this->faker->address(),
            'phone' => '+201'.$this->faker->numerify('#########'),
        ];
    }
}
