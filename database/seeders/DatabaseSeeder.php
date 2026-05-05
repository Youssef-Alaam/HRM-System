<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            OrganizationSeeder::class,
            OfficeSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            HolidaySeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            DocumentTypeSeeder::class,
            AssetCategorySeeder::class,
            AssetSeeder::class,
            LeaveTypeSeeder::class,
        ]);
    }
}
