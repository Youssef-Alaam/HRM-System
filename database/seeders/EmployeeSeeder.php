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

        // Sequential rather than count(N): each row's afterMaking computes
        // its EMP-{type}{NNNN} code from the current DB state, so we need
        // the previous row to be inserted before the next one is built.
        // Batched count(N)->create() resolves all afterMaking callbacks
        // up-front and would hand every row the same code (unique violation).
        $managers = collect();
        for ($i = 0; $i < 4; $i++) {
            $dept = $departments->random();
            $managers->push(Employee::factory()->create([
                'org_id' => $org->id,
                'office_id' => $office?->id,
                'department_id' => $dept->id,
                'position_id' => $positions->where('department_id', $dept->id)->random()?->id,
            ]));
        }

        // 22 ICs distributed across the managers (total = 22 + 4 = 26 +
        // 4 test users wired by the test-user seeder = ~30).
        for ($i = 0; $i < 22; $i++) {
            $dept = $departments->random();
            $manager = $managers->random();
            Employee::factory()->create([
                'org_id' => $org->id,
                'office_id' => $office?->id,
                'department_id' => $dept->id,
                'position_id' => $positions->where('department_id', $dept->id)->random()?->id,
                'manager_id' => $manager->id,
            ]);
        }
    }
}
