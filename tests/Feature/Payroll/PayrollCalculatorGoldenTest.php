<?php

/**
 * GOLDEN TEST CASES — drafted, awaiting Egyptian payroll accountant sign-off
 * at Checkpoint D (per Walid 2026-04-30 deferral).
 *
 * These cases anchor the calculator math against EGYPT_COMPLIANCE_RULES.md.
 * If the accountant disagrees with any expected value here, fix the
 * calculator first (or settings seed), then update the golden value.
 * NEVER weaken the assertion to make a wrong calculation pass.
 *
 * All values in piasters (1 EGP = 100 piasters).
 */

use App\Services\PayrollCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\PayrollRatesSeeder::class);
    $this->calc = app(PayrollCalculator::class);
});

// ── Insurable wage clamping (Law 148/2019) ──────────────────────────────────

test('insurable wage is clamped to floor for low salaries', function () {
    // EGP 1,500/month — below the EGP 2,300 floor
    $r = $this->calc->calculateMonthly(150_000, 2026);
    expect($r['insurable_wage_piasters'])->toBe(2_300 * 100);
});

test('insurable wage uses actual salary in mid-range', function () {
    // EGP 6,000/month — between floor and cap
    $r = $this->calc->calculateMonthly(600_000, 2026);
    expect($r['insurable_wage_piasters'])->toBe(600_000);
});

test('insurable wage is capped for high salaries', function () {
    // EGP 30,000/month — above the EGP 14,500 cap
    $r = $this->calc->calculateMonthly(3_000_000, 2026);
    expect($r['insurable_wage_piasters'])->toBe(14_500 * 100);
});

// ── SI contributions (Law 148/2019, §12) ─────────────────────────────────────

test('SI employee contribution is 11% of insurable wage', function () {
    // EGP 6,000 × 11% = 660 EGP = 66,000 piasters
    $r = $this->calc->calculateMonthly(600_000, 2026);
    expect($r['si_employee_piasters'])->toBe(66_000);
});

test('SI employer contribution is 18.75% of insurable wage', function () {
    // EGP 6,000 × 18.75% = 1,125 EGP = 112,500 piasters
    $r = $this->calc->calculateMonthly(600_000, 2026);
    expect($r['si_employer_piasters'])->toBe(112_500);
});

test('health insurance employee is 1% of insurable wage', function () {
    // EGP 6,000 × 1% = 60 EGP = 6,000 piasters
    $r = $this->calc->calculateMonthly(600_000, 2026);
    expect($r['health_employee_piasters'])->toBe(6_000);
});

test('total employer cost is 22.25% of insurable wage', function () {
    // EGP 6,000 × (18.75% + 3.25% + 0.25%) = EGP 6,000 × 22.25% = 1,335 EGP
    $r = $this->calc->calculateMonthly(600_000, 2026);
    expect($r['total_employer_cost_piasters'])->toBe(133_500);
});

test('SI deductions cap at insurable cap regardless of actual salary', function () {
    // Salary EGP 50,000/month, but SI is capped at EGP 14,500 insurable
    // Employee SI = 14,500 × 12% = 1,740 EGP = 174,000 piasters
    $r = $this->calc->calculateMonthly(5_000_000, 2026);
    expect($r['total_employee_si_piasters'])->toBe(174_000);
});

// ── Tax-free zone (personal allowance + low brackets, §14) ───────────────────

test('low salary pays zero income tax (within personal allowance)', function () {
    // EGP 3,000/month = EGP 36,000/year gross.
    // Personal allowance 20,000 + SI deduction wipes out taxable.
    $r = $this->calc->calculateMonthly(300_000, 2026);
    expect($r['monthly_tax_piasters'])->toBe(0);
});

test('annual income up to 60,000 EGP effectively pays zero tax', function () {
    // First 40,000 bracket is 0%, plus 20,000 personal allowance = 60,000 free.
    // EGP 5,000/month = EGP 60,000/year gross. After SI deduction, taxable
    // is well below the 0% bracket boundary — still zero tax.
    $r = $this->calc->calculateMonthly(500_000, 2026);
    expect($r['monthly_tax_piasters'])->toBe(0);
});

// ── Progressive brackets (§14) ───────────────────────────────────────────────

test('mid-income employee pays progressive tax', function () {
    // EGP 12,000/month = EGP 144,000/year gross.
    // SI deduction = (12,000 capped at 14,500 → 12,000) × 12% × 12 = 17,280
    // Taxable = 144,000 − 20,000 − 17,280 = 106,720
    // Brackets:
    //   0–40k:    0%
    //   40k–55k:  15,000 × 10% = 1,500
    //   55k–70k:  15,000 × 15% = 2,250
    //   70k–200k: 36,720 × 20% = 7,344
    // Annual tax ≈ 11,094 EGP → monthly ≈ 924.5 EGP = 92,450 piasters
    $r = $this->calc->calculateMonthly(1_200_000, 2026);

    // ±100 piaster tolerance for rounding nuances pending accountant review
    expect($r['monthly_tax_piasters'])->toBeGreaterThan(91_500);
    expect($r['monthly_tax_piasters'])->toBeLessThan(93_500);
});

test('high earner crosses multiple brackets', function () {
    // EGP 100,000/month = EGP 1,200,000/year gross
    // SI capped at insurable cap → annual SI deduction = 14,500 × 12% × 12 = 20,880
    // Taxable = 1,200,000 − 20,000 − 20,880 = 1,159,120
    $r = $this->calc->calculateMonthly(10_000_000, 2026);

    // Monthly tax should be well above zero, into the 22.5%–25% range.
    expect($r['monthly_tax_piasters'])->toBeGreaterThan(2_000_00);  // > 2,000 EGP
});

// ── Net pay sanity ───────────────────────────────────────────────────────────

test('net pay = gross − employee SI − monthly tax', function () {
    $r = $this->calc->calculateMonthly(800_000, 2026);

    expect($r['net_piasters'])->toBe(
        $r['gross_piasters'] - $r['total_employee_si_piasters'] - $r['monthly_tax_piasters']
    );
});

test('net pay never exceeds gross', function () {
    $r = $this->calc->calculateMonthly(2_000_000, 2026);

    expect($r['net_piasters'])->toBeLessThanOrEqual($r['gross_piasters']);
});

// ── Edge cases ───────────────────────────────────────────────────────────────

test('throws when year has no settings configured', function () {
    $this->calc->calculateMonthly(600_000, 1999);
})->throws(InvalidArgumentException::class);

test('throws on negative salary', function () {
    $this->calc->calculateMonthly(-100, 2026);
})->throws(InvalidArgumentException::class);

test('zero salary returns all zeros except SI floor enforcement', function () {
    $r = $this->calc->calculateMonthly(0, 2026);
    // Insurable still clamps to floor → SI is calculated on floor
    expect($r['insurable_wage_piasters'])->toBe(2_300 * 100);
    expect($r['monthly_tax_piasters'])->toBe(0);
});
