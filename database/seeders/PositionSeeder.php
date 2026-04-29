<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();

        $positionsByDept = [
            'Technical' => [
                ['title' => 'Software Engineer', 'level' => 1],
                ['title' => 'Senior Software Engineer', 'level' => 2],
                ['title' => 'Engineering Manager', 'level' => 3],
                ['title' => 'QA Engineer', 'level' => 1],
                ['title' => 'DevOps Engineer', 'level' => 2],
            ],
            'Sales' => [
                ['title' => 'Sales Representative', 'level' => 1],
                ['title' => 'Account Manager', 'level' => 2],
                ['title' => 'Sales Lead', 'level' => 3],
            ],
            'HR' => [
                ['title' => 'HR Specialist', 'level' => 1],
                ['title' => 'HR Manager', 'level' => 3],
            ],
            'Operations' => [
                ['title' => 'Operations Coordinator', 'level' => 1],
                ['title' => 'Office Manager', 'level' => 2],
                ['title' => 'Finance Officer', 'level' => 2],
            ],
        ];

        foreach ($positionsByDept as $deptName => $positions) {
            $dept = Department::query()->where('org_id', $org->id)->where('name', $deptName)->first();
            if (! $dept) {
                continue;
            }

            foreach ($positions as $position) {
                Position::firstOrCreate(
                    ['org_id' => $org->id, 'department_id' => $dept->id, 'title' => $position['title']],
                    ['level' => $position['level'], 'is_active' => true],
                );
            }
        }
    }
}
