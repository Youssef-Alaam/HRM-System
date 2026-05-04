<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Position;
use App\Support\PositionType;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();

        $positionsByDept = [
            'Technical' => [
                ['title' => 'Software Engineer', 'level' => 1, 'type_code' => PositionType::ENGINEERING],
                ['title' => 'Senior Software Engineer', 'level' => 2, 'type_code' => PositionType::ENGINEERING],
                ['title' => 'Engineering Manager', 'level' => 3, 'type_code' => PositionType::ENGINEERING],
                ['title' => 'QA Engineer', 'level' => 1, 'type_code' => PositionType::ENGINEERING],
                ['title' => 'DevOps Engineer', 'level' => 2, 'type_code' => PositionType::ENGINEERING],
            ],
            'Sales' => [
                ['title' => 'Sales Representative', 'level' => 1, 'type_code' => PositionType::SALES],
                ['title' => 'Account Manager', 'level' => 2, 'type_code' => PositionType::SALES],
                ['title' => 'Sales Lead', 'level' => 3, 'type_code' => PositionType::SALES],
            ],
            'HR' => [
                ['title' => 'HR Specialist', 'level' => 1, 'type_code' => PositionType::HR],
                ['title' => 'HR Manager', 'level' => 3, 'type_code' => PositionType::HR],
            ],
            'Operations' => [
                ['title' => 'Operations Coordinator', 'level' => 1, 'type_code' => PositionType::OPERATIONS],
                ['title' => 'Office Manager', 'level' => 2, 'type_code' => PositionType::OPERATIONS],
                ['title' => 'Finance Officer', 'level' => 2, 'type_code' => PositionType::FINANCE],
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
                    [
                        'level' => $position['level'],
                        'type_code' => $position['type_code'],
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
