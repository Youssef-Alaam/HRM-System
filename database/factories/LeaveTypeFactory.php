<?php

namespace Database\Factories;

use App\Models\LeaveType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'org_id'                          => fn () => Organization::factory()->create()->id,
            'code'                            => $this->faker->unique()->slug(2),
            'name'                            => $this->faker->words(3, true),
            'default_balance_days'            => 21,
            'requires_certificate_after_days' => null,
            'advance_notice_days'             => null,
            'is_right_not_discretion'         => false,
            'applies_to'                      => 'all',
            'sort_order'                      => 0,
            'is_active'                       => true,
            'version'                         => 1,
        ];
    }
}
