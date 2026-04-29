<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * A small Egyptian-leaning name pool. Faker locale can be unreliable
     * across versions; an explicit pool keeps seeds deterministic-ish and
     * culturally appropriate for the demo dataset.
     */
    private const FIRST_NAMES_MALE = [
        'Ahmed', 'Mohamed', 'Mahmoud', 'Omar', 'Khaled', 'Youssef', 'Karim',
        'Hossam', 'Tamer', 'Hatem', 'Sherif', 'Amr', 'Tarek', 'Walid',
        'Bassem', 'Adel', 'Hany', 'Sameh', 'Wael', 'Hesham',
    ];

    private const FIRST_NAMES_FEMALE = [
        'Sara', 'Mariam', 'Nour', 'Yasmine', 'Dina', 'Rana', 'Heba',
        'Mona', 'Salma', 'Engy', 'Aya', 'Reem', 'Nada', 'Farida',
        'Hagar', 'Donia', 'Rasha', 'Marwa',
    ];

    private const LAST_NAMES = [
        'Hassan', 'Mostafa', 'Ibrahim', 'Said', 'Abdelrahman', 'ElSayed',
        'Mahmoud', 'AbdelAziz', 'Khalil', 'Farouk', 'Mansour', 'Saleh',
        'AbdelHamid', 'Fawzy', 'Soliman', 'ElHennawy', 'ElGohary',
        'Selim', 'Aboukhalil', 'Naguib',
    ];

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);
        $first = $this->faker->randomElement(
            $gender === 'male' ? self::FIRST_NAMES_MALE : self::FIRST_NAMES_FEMALE
        );
        $last = $this->faker->randomElement(self::LAST_NAMES);

        $hiringDate = $this->faker->dateTimeBetween('-5 years', '-1 month');

        return [
            'org_id' => fn () => Organization::query()->first()?->id ?? Organization::factory()->create()->id,
            'employee_code' => 'EMP-'.strtoupper(Str::random(6)),
            'first_name' => $first,
            'last_name' => $last,
            'email' => Str::lower($first.'.'.$last.'+'.Str::random(4)).'@yzh.test',
            'phone' => '+201'.$this->faker->numerify('#########'),
            'national_id' => $this->faker->numerify('##############'),
            'date_of_birth' => $this->faker->dateTimeBetween('-55 years', '-22 years')->format('Y-m-d'),
            'gender' => $gender,
            'marital_status' => $this->faker->randomElement(['single', 'married', 'divorced']),
            'nationality' => 'Egyptian',
            'address' => $this->faker->address(),
            'emergency_contact_name' => $this->faker->randomElement(self::FIRST_NAMES_MALE).' '.$this->faker->randomElement(self::LAST_NAMES),
            'emergency_contact_phone' => '+201'.$this->faker->numerify('#########'),
            'position_id' => fn () => Position::query()->inRandomOrder()->first()?->id,
            'department_id' => fn () => Department::query()->inRandomOrder()->first()?->id,
            'office_id' => fn () => Office::query()->inRandomOrder()->first()?->id,
            'hiring_date' => $hiringDate->format('Y-m-d'),
            'contract_type' => $this->faker->randomElement(['probation', 'fixed', 'unlimited']),
            'contract_start_date' => $hiringDate->format('Y-m-d'),
            'employment_status' => 'active',
            'workweek_days' => ['sun', 'mon', 'tue', 'wed', 'thu'],
            'shift_start_time' => '09:00',
            'shift_end_time' => '17:00',
            'timezone' => 'Africa/Cairo',
            // 5,000 to 50,000 EGP per month, in piasters
            'base_salary_piasters' => $this->faker->numberBetween(500_000, 5_000_000),
            'annual_leave_balance_days' => 21,
            'sick_leave_balance_days' => 30,
            'casual_leave_balance_days' => 6,
            'permissions_balance_minutes' => 0,
            'emergency_credit_days' => 0,
            'comp_day_balance' => 0,
            'is_expat' => false,
            'speaks_arabic' => true,
        ];
    }
}
