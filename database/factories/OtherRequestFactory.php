<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OtherRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'org_id' => Organization::factory(),
            'employee_id' => Employee::factory(),
            'type' => $this->faker->randomElement(['overtime', 'expense_claim', 'change_shift', 'holiday_work']),
            'status' => 'pending',
            'request_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'hours_requested' => null,
            'amount_piasters' => null,
            'notes' => $this->faker->sentence(),
        ];
    }

    public function overtime(): static
    {
        return $this->state(['type' => 'overtime', 'hours_requested' => 1.5]);
    }

    public function expenseClaim(): static
    {
        return $this->state(['type' => 'expense_claim', 'amount_piasters' => 5000]);
    }
}
