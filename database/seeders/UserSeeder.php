<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Creates four test users (admin / hr / manager / employee) with matching
     * Employee records assigned to YZH Solutions departments. Password is
     * "password" for every test user — local dev only, never seed prod.
     */
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();
        $office = Office::query()->where('org_id', $org->id)->first();

        $deptHR = Department::query()->where('org_id', $org->id)->where('name', 'HR')->first();
        $deptTech = Department::query()->where('org_id', $org->id)->where('name', 'Technical')->first();
        $deptOps = Department::query()->where('org_id', $org->id)->where('name', 'Operations')->first();

        $hrManagerPosition = Position::query()->where('org_id', $org->id)->where('title', 'HR Manager')->first();
        $hrSpecialistPosition = Position::query()->where('org_id', $org->id)->where('title', 'HR Specialist')->first();
        $engineerPosition = Position::query()->where('org_id', $org->id)->where('title', 'Software Engineer')->first();
        $engManagerPosition = Position::query()->where('org_id', $org->id)->where('title', 'Engineering Manager')->first();
        $opsCoordPosition = Position::query()->where('org_id', $org->id)->where('title', 'Operations Coordinator')->first();

        $seeds = [
            [
                'email' => 'admin@yzh.test',
                'name' => 'Admin User',
                'role' => RoleDefinitions::ROLE_ADMIN,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'department' => $deptOps,
                'position' => $opsCoordPosition,
            ],
            [
                'email' => 'hr@yzh.test',
                'name' => 'Sarah Hassan',
                'role' => RoleDefinitions::ROLE_HR,
                'first_name' => 'Sarah',
                'last_name' => 'Hassan',
                'department' => $deptHR,
                'position' => $hrManagerPosition,
            ],
            [
                'email' => 'manager@yzh.test',
                'name' => 'Khaled Mostafa',
                'role' => RoleDefinitions::ROLE_MANAGER,
                'first_name' => 'Khaled',
                'last_name' => 'Mostafa',
                'department' => $deptTech,
                'position' => $engManagerPosition,
            ],
            [
                'email' => 'employee@yzh.test',
                'name' => 'Nour Ibrahim',
                'role' => RoleDefinitions::ROLE_EMPLOYEE,
                'first_name' => 'Nour',
                'last_name' => 'Ibrahim',
                'department' => $deptTech,
                'position' => $engineerPosition,
            ],
        ];

        foreach ($seeds as $seed) {
            $employee = Employee::firstOrCreate(
                ['org_id' => $org->id, 'email' => $seed['email']],
                [
                    'employee_code' => 'EMP-'.strtoupper(Str::random(6)),
                    'first_name' => $seed['first_name'],
                    'last_name' => $seed['last_name'],
                    'phone' => '+201111111111',
                    'nationality' => 'Egyptian',
                    'gender' => $seed['first_name'] === 'Sarah' || $seed['first_name'] === 'Nour' ? 'female' : 'male',
                    'department_id' => $seed['department']?->id,
                    'position_id' => $seed['position']?->id,
                    'office_id' => $office?->id,
                    'hiring_date' => now()->subYears(2)->format('Y-m-d'),
                    'contract_type' => 'unlimited',
                    'employment_status' => 'active',
                    'workweek_days' => ['sun', 'mon', 'tue', 'wed', 'thu'],
                    'shift_start_time' => '09:00',
                    'shift_end_time' => '17:00',
                    'base_salary_piasters' => 2_000_000,
                    'annual_leave_balance_days' => 21,
                    'sick_leave_balance_days' => 30,
                    'casual_leave_balance_days' => 6,
                ],
            );

            $user = User::firstOrCreate(
                ['email' => $seed['email']],
                [
                    'org_id' => $org->id,
                    'name' => $seed['name'],
                    'password' => Hash::make('password'),
                    'employee_id' => $employee->id,
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->hasRole($seed['role'])) {
                $user->assignRole($seed['role']);
            }
        }
    }
}
