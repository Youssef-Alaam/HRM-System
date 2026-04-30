<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'org_id' => Organization::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->sentence(),
            'level' => fake()->numberBetween(1, 5),
            'is_active' => true,
            'version' => 1,
        ];
    }
}
