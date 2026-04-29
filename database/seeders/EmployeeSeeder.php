<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();
        $office = Office::query()->where('org_id', $org->id)->first();
        $departments = Department::query()->where('org_id', $org->id)->get();
        $positions = Position::query()->where('org_id', $org->id)->get();

        if ($departments->isEmpty() || $positions->isEmpty()) {
            return;
        }

        // Make managers first so we can assign reports under them.
        $managers = Employee::factory()
            ->count(4)
            ->state(function () use ($org, $office, $departments, $positions) {
                $dept = $departments->random();

                return [
                    'org_id' => $org->id,
                    'office_id' => $office?->id,
                    'department_id' => $dept->id,
                    'position_id' => $positions->where('department_id', $dept->id)->random()?->id,
                ];
            })
            ->create();

        // Then 22 ICs distributed across managers (so total = 22 + 4 = 26 + 4 test users = ~30).
        Employee::factory()
            ->count(22)
            ->state(function () use ($org, $office, $departments, $positions, $managers) {
                $dept = $departments->random();
                $manager = $managers->random();

                return [
                    'org_id' => $org->id,
                    'office_id' => $office?->id,
                    'department_id' => $dept->id,
                    'position_id' => $positions->where('department_id', $dept->id)->random()?->id,
                    'manager_id' => $manager->id,
                ];
            })
            ->create();
    }
}
