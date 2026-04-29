<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Egyptian public holidays for 2026.
     *
     * Lunar (Islamic) holiday dates are estimates per the standard reference
     * Hijri-to-Gregorian conversion and may shift by a day after official
     * gov declaration. HR can correct via the Holidays admin once F-Phase
     * lands. Fixed-date holidays here are the authoritative ones.
     */
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();

        $holidays = [
            ['date' => '2026-01-07', 'name' => 'Coptic Christmas'],
            ['date' => '2026-01-25', 'name' => 'Revolution Day & Police Day'],
            ['date' => '2026-04-13', 'name' => 'Sham el-Nessim'],            // Coptic Easter Monday (estimate)
            ['date' => '2026-04-25', 'name' => 'Sinai Liberation Day'],
            ['date' => '2026-05-01', 'name' => 'Labor Day'],
            ['date' => '2026-03-21', 'name' => 'Eid el-Fitr (day 1)'],       // estimate
            ['date' => '2026-03-22', 'name' => 'Eid el-Fitr (day 2)'],
            ['date' => '2026-03-23', 'name' => 'Eid el-Fitr (day 3)'],
            ['date' => '2026-05-28', 'name' => 'Eid el-Adha (day 1)'],       // estimate
            ['date' => '2026-05-29', 'name' => 'Eid el-Adha (day 2)'],
            ['date' => '2026-05-30', 'name' => 'Eid el-Adha (day 3)'],
            ['date' => '2026-05-31', 'name' => 'Eid el-Adha (day 4)'],
            ['date' => '2026-06-17', 'name' => 'Islamic New Year'],          // estimate
            ['date' => '2026-06-30', 'name' => '30 June Revolution Day'],
            ['date' => '2026-07-23', 'name' => '23 July Revolution Day'],
            ['date' => '2026-08-26', 'name' => 'Prophet Muhammad\'s Birthday'], // estimate
            ['date' => '2026-10-06', 'name' => 'Armed Forces Day'],
        ];

        foreach ($holidays as $holiday) {
            $exists = Holiday::query()
                ->where('org_id', $org->id)
                ->whereDate('date', $holiday['date'])
                ->exists();

            if ($exists) {
                continue;
            }

            Holiday::create([
                'org_id' => $org->id,
                'date' => $holiday['date'],
                'name' => $holiday['name'],
                'is_recurring' => false,
                'is_make_up' => false,
            ]);
        }
    }
}
