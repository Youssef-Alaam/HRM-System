<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $end   = $this->faker->dateTimeBetween($start, '+40 days');

        return [
            'org_id'               => fn () => Organization::factory()->create()->id,
            'employee_id'          => fn () => Employee::factory()->create()->id,
            'leave_type_id'        => fn () => LeaveType::factory()->create()->id,
            'start_date'           => $start->format('Y-m-d'),
            'end_date'             => $end->format('Y-m-d'),
            'days_count'           => $this->faker->numberBetween(1, 5),
            'status'               => 'pending',
            'reason'               => $this->faker->sentence(),
            'attachment_path'      => null,
            'approved_by_user_id'  => null,
            'approved_at'          => null,
            'rejected_reason'      => null,
            'cancelled_at'         => null,
            'version'              => 1,
        ];
    }
}
