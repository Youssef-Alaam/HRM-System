<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->first();
        if (! $org) {
            return;
        }

        $types = [
            [
                // Per Labor Law 14/2025 Art. 89 — annual leave entitlement scales by tenure
                'code'                   => 'annual',
                'name'                   => 'Annual Leave',
                'default_balance_days'   => 21,
                'is_right_not_discretion' => false,
                'applies_to'             => 'all',
                'sort_order'             => 1,
            ],
            [
                // Per Labor Law 14/2025 Art. 54 — sick leave is a right, not discretionary
                'code'                            => 'sick',
                'name'                            => 'Sick Leave',
                'default_balance_days'            => 90,
                'requires_certificate_after_days' => 3,
                'is_right_not_discretion'         => true,
                'applies_to'                      => 'all',
                'sort_order'                      => 2,
            ],
            [
                // Per Labor Law 14/2025 Art. 90 — casual leave 7 days/year
                'code'                   => 'casual',
                'name'                   => 'Casual Leave',
                'default_balance_days'   => 7,
                'is_right_not_discretion' => true,
                'applies_to'             => 'all',
                'sort_order'             => 3,
            ],
            [
                // Per Labor Law 14/2025 Art. 93 (Law 29/2025) — maternity 120 days, female only
                'code'                   => 'maternity',
                'name'                   => 'Maternity Leave',
                'default_balance_days'   => 120,
                'is_right_not_discretion' => true,
                'applies_to'             => 'female',
                'sort_order'             => 4,
            ],
            [
                // Per Labor Law 14/2025 Art. 93 bis — paternity 1 day per birth, max 3 instances
                'code'                   => 'paternity',
                'name'                   => 'Paternity Leave',
                'default_balance_days'   => 3,
                'is_right_not_discretion' => true,
                'applies_to'             => 'male',
                'sort_order'             => 5,
            ],
            [
                // Per Labor Law 14/2025 Art. 94 — study leave requires 10-day advance notice
                'code'                   => 'study',
                'name'                   => 'Study Leave',
                'default_balance_days'   => 10,
                'advance_notice_days'    => 10,
                'is_right_not_discretion' => false,
                'applies_to'             => 'all',
                'sort_order'             => 6,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(
                ['org_id' => $org->id, 'code' => $type['code']],
                array_merge($type, ['org_id' => $org->id, 'is_active' => true]),
            );
        }
    }
}
