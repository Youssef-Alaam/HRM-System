<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();

        $departments = [
            ['name' => 'Technical', 'description' => 'Software engineering, infrastructure, and product development.'],
            ['name' => 'Sales', 'description' => 'New business, account management, and partnerships.'],
            ['name' => 'HR', 'description' => 'People operations, payroll, recruiting, employee relations.'],
            ['name' => 'Operations', 'description' => 'Office management, finance, procurement, admin.'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['org_id' => $org->id, 'name' => $dept['name']],
                ['description' => $dept['description'], 'is_active' => true],
            );
        }
    }
}
