<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Position;
use App\Support\PositionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        $title = fake()->jobTitle();

        return [
            'org_id' => Organization::factory(),
            'title' => $title,
            'description' => fake()->sentence(),
            'level' => fake()->numberBetween(1, 5),
            'type_code' => PositionType::inferFromTitle($title),
            'is_active' => true,
            'version' => 1,
        ];
    }
}
