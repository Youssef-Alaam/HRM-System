<?php

namespace Database\Factories;

use App\Models\Holiday;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'org_id'       => fn () => Organization::factory()->create()->id,
            'name'         => $this->faker->words(3, true),
            'date'         => $this->faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'is_recurring' => false,
            'is_make_up'   => false,
            'description'  => null,
            'version'      => 1,
        ];
    }
}
