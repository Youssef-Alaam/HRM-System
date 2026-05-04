<?php

namespace Tests\Feature\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_creates_one_organization_named_yzh_solutions(): void
    {
        $this->assertSame(1, Organization::count());
        $this->assertNotNull(Organization::query()->where('name', 'YZH Solutions')->first());
    }

    public function test_creates_avenue_mall_office_with_real_coordinates(): void
    {
        $office = Office::query()->where('name', 'Avenue Mall — Rehab')->first();
        $this->assertNotNull($office);
        $this->assertEqualsWithDelta(30.06253, (float) $office->latitude, 0.001);
        $this->assertEqualsWithDelta(31.48951, (float) $office->longitude, 0.001);
        $this->assertSame(150, $office->allowed_check_in_radius_meters);
    }

    public function test_creates_four_departments(): void
    {
        $this->assertSame(4, Department::count());
        foreach (['Technical', 'Sales', 'HR', 'Operations'] as $name) {
            $this->assertNotNull(Department::query()->where('name', $name)->first());
        }
    }

    public function test_creates_positions_per_department(): void
    {
        $this->assertGreaterThanOrEqual(10, Position::count());

        foreach (['Technical', 'Sales', 'HR', 'Operations'] as $deptName) {
            $dept = Department::query()->where('name', $deptName)->first();
            $this->assertGreaterThan(0, Position::query()->where('department_id', $dept->id)->count(), "{$deptName} should have at least one position");
        }
    }

    public function test_creates_egyptian_2026_public_holidays(): void
    {
        $this->assertGreaterThanOrEqual(15, Holiday::count(), 'Expect a substantial 2026 holiday list');

        $required = [
            '2026-01-07' => 'Coptic Christmas',
            '2026-01-25' => 'Revolution Day & Police Day',
            '2026-04-25' => 'Sinai Liberation Day',
            '2026-05-01' => 'Labor Day',
            '2026-06-30' => '30 June Revolution Day',
            '2026-07-23' => '23 July Revolution Day',
            '2026-10-06' => 'Armed Forces Day',
        ];

        foreach ($required as $date => $name) {
            $holiday = Holiday::query()->whereDate('date', $date)->first();
            $this->assertNotNull($holiday, "Missing holiday on {$date}");
            $this->assertSame($name, $holiday->name);
        }
    }

    public function test_creates_four_test_users_with_correct_roles(): void
    {
        $expected = [
            'admin@yzh.test' => RoleDefinitions::ROLE_ADMIN,
            'hr@yzh.test' => RoleDefinitions::ROLE_HR,
            'manager@yzh.test' => RoleDefinitions::ROLE_MANAGER,
            'employee@yzh.test' => RoleDefinitions::ROLE_EMPLOYEE,
        ];

        foreach ($expected as $email => $role) {
            $user = User::query()->where('email', $email)->first();
            $this->assertNotNull($user, "User {$email} should exist");
            $this->assertTrue($user->hasRole($role), "User {$email} should have role {$role}");
            $this->assertNotNull($user->employee_id, "User {$email} should be linked to an Employee");
            $this->assertTrue(Hash::check('password', $user->password), "User {$email} password should be 'password'");
        }
    }

    public function test_seeds_at_least_twenty_five_employees(): void
    {
        $this->assertGreaterThanOrEqual(25, Employee::count());
        $this->assertLessThanOrEqual(35, Employee::count());
    }

    public function test_random_employees_have_managers_assigned(): void
    {
        $withManagers = Employee::query()->whereNotNull('manager_id')->count();
        $this->assertGreaterThan(0, $withManagers, 'Some employees should report to managers');
    }

    public function test_seeded_employees_use_emp_xxxxx_code_format(): void
    {
        $codes = Employee::query()->pluck('employee_code');

        $this->assertGreaterThan(0, $codes->count(), 'Seeder should produce employees');
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression(
                '/^EMP-\d{5}$/',
                $code,
                "Seeded employee_code does not match EMP-XXXXX: {$code}",
            );
        }

        // No duplicates across the seeded set — the (org_id, employee_code)
        // unique index enforces this in MySQL, but verifying here surfaces
        // a regression if the index ever drops or the generator drifts.
        $this->assertSame(
            $codes->count(),
            $codes->unique()->count(),
            'EMP-XXXXX codes must be unique across the seeded set',
        );
    }

    public function test_seeded_positions_have_a_type_code_in_the_valid_range(): void
    {
        $positions = Position::all();

        $this->assertGreaterThan(0, $positions->count());
        foreach ($positions as $position) {
            $this->assertNotNull($position->type_code, "Position {$position->title} missing type_code");
            $this->assertGreaterThanOrEqual(0, (int) $position->type_code);
            $this->assertLessThanOrEqual(9, (int) $position->type_code);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Organization::count());
        $this->assertSame(4, Department::count());
        $this->assertSame(4, User::count());
    }
}
