<?php

namespace Database\Seeders;

use App\Models\PayrollSetting;
use App\Models\TaxBracket;
use Illuminate\Database\Seeder;

/**
 * Seeds Egyptian income tax brackets + payroll constants by year.
 * Source: EGYPT_COMPLIANCE_RULES.md §14 (income tax) + §12 (SI).
 *
 * Per Decision/ANA-3.0 — these are the canonical values used by the
 * payroll calculator. Edit through admin Settings UI; never hard-code
 * in calculations.
 */
class PayrollRatesSeeder extends Seeder
{
    public function run(): void
    {
        // Income Tax Law 91/2005, 2026 progressive brackets (annual EGP).
        // Stored in piasters (× 100).
        $brackets2026 = [
            ['tier' => 1, 'min' => 0,                'max' => 40_000,    'rate' => 0.0000],
            ['tier' => 2, 'min' => 40_001,           'max' => 55_000,    'rate' => 0.1000],
            ['tier' => 3, 'min' => 55_001,           'max' => 70_000,    'rate' => 0.1500],
            ['tier' => 4, 'min' => 70_001,           'max' => 200_000,   'rate' => 0.2000],
            ['tier' => 5, 'min' => 200_001,          'max' => 400_000,   'rate' => 0.2250],
            ['tier' => 6, 'min' => 400_001,          'max' => 1_200_000, 'rate' => 0.2500],
            ['tier' => 7, 'min' => 1_200_001,        'max' => 2_400_000, 'rate' => 0.2750],
            ['tier' => 8, 'min' => 2_400_001,        'max' => null,      'rate' => 0.3000],
        ];

        foreach ($brackets2026 as $b) {
            TaxBracket::updateOrCreate(
                ['year' => 2026, 'tier' => $b['tier']],
                [
                    'min_piasters' => $b['min'] * 100,
                    'max_piasters' => $b['max'] !== null ? $b['max'] * 100 : null,
                    'rate' => $b['rate'],
                ],
            );
        }

        // Social Insurance Law 148/2019 — 2026 rates + caps.
        PayrollSetting::updateOrCreate(
            ['year' => 2026],
            [
                'personal_allowance_piasters' => 20_000 * 100,   // EGP 20,000/year tax-free
                'si_insurable_floor_piasters' => 2_300 * 100,    // EGP 2,300/month minimum
                'si_insurable_cap_piasters'   => 14_500 * 100,   // EGP 14,500/month maximum
                'si_employee_rate'            => 0.1100,         // 11%
                'si_employer_rate'            => 0.1875,         // 18.75%
                'health_employee_rate'        => 0.0100,         // 1%
                'health_employer_rate'        => 0.0325,         // 3.25%
                'training_employer_rate'      => 0.0025,         // 0.25%
                'minimum_wage_piasters'       => 7_000 * 100,    // EGP 7,000/month (private sector)
            ],
        );
    }
}
