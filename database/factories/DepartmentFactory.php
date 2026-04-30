<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'org_id' => Organization::factory(),
            'name' => fake()->unique()->randomElement([
                'Technical', 'Sales', 'HR', 'Operations', 'Marketing', 'Finance', 'Legal',
            ]),
            'description' => fake()->sentence(),
            'is_active' => true,
            'version' => 1,
        ];
    }
}
