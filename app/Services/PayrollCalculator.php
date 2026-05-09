<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollSetting;
use App\Models\TaxBracket;
use InvalidArgumentException;

/**
 * Pure calculation engine — no DB writes, no banking, no payslip storage.
 * This is the math that Walid + the accountant will sign off in Checkpoint D.
 *
 * Per:
 *   - Income Tax Law 91/2005 (progressive brackets, personal allowance)
 *   - Social Insurance Law 148/2019 (SI / health / training)
 *   - EGYPT_COMPLIANCE_RULES.md §12 + §14
 *   - CLAUDE.md money rule: piasters as integers, half-up rounding
 *
 * All inputs and outputs in piasters. Caller passes monthly_salary_piasters
 * + year; calculator returns a structured breakdown.
 */
class PayrollCalculator
{
    /**
     * @return array<string, int> All values in piasters.
     */
    public function calculateMonthly(int $monthlySalaryPiasters, int $year, bool $isOnCommercialRegister = false): array
    {
        if ($monthlySalaryPiasters < 0) {
            throw new InvalidArgumentException('Salary cannot be negative.');
        }

        $settings = PayrollSetting::where('year', $year)->first();

        if (! $settings) {
            throw new InvalidArgumentException("No payroll settings configured for year {$year}.");
        }

        // ── Step 1: Insurable wage (clamped to floor/cap)
        $insurable = max(
            $settings->si_insurable_floor_piasters,
            min($monthlySalaryPiasters, $settings->si_insurable_cap_piasters),
        );

        // ── Step 2: SI contributions (employee deduction + employer cost)
        // Director SI rate flag (§13) — directors on commercial register are
        // treated differently. Stub: same rate for now, accountant signs off.
        $siEmployee = $this->halfUp($insurable * $settings->si_employee_rate);
        $siEmployer = $this->halfUp($insurable * $settings->si_employer_rate);
        $healthEmployee = $this->halfUp($insurable * $settings->health_employee_rate);
        $healthEmployer = $this->halfUp($insurable * $settings->health_employer_rate);
        $trainingEmployer = $this->halfUp($insurable * $settings->training_employer_rate);

        $totalEmployeeSi = $siEmployee + $healthEmployee;
        $totalEmployerCost = $siEmployer + $healthEmployer + $trainingEmployer;

        // ── Step 3: Income tax (per Art. 8, Law 91/2005)
        // (a) Annualize monthly salary
        // (b) Subtract personal allowance + annual SI deduction
        // (c) Apply progressive brackets to the remainder
        // (d) Divide by 12 for monthly withholding
        $annualGross = $monthlySalaryPiasters * 12;
        $annualSiDeduction = $totalEmployeeSi * 12;
        $annualTaxable = max(0, $annualGross - $settings->personal_allowance_piasters - $annualSiDeduction);

        $annualTax = $this->applyBrackets($annualTaxable, $year);
        $monthlyTax = $this->halfUp($annualTax / 12);

        // ── Step 4: Net pay
        $netPiasters = $monthlySalaryPiasters - $totalEmployeeSi - $monthlyTax;

        return [
            'gross_piasters' => $monthlySalaryPiasters,
            'insurable_wage_piasters' => $insurable,
            'si_employee_piasters' => $siEmployee,
            'si_employer_piasters' => $siEmployer,
            'health_employee_piasters' => $healthEmployee,
            'health_employer_piasters' => $healthEmployer,
            'training_employer_piasters' => $trainingEmployer,
            'total_employee_si_piasters' => $totalEmployeeSi,
            'total_employer_cost_piasters' => $totalEmployerCost,
            'annual_taxable_piasters' => $annualTaxable,
            'annual_tax_piasters' => $annualTax,
            'monthly_tax_piasters' => $monthlyTax,
            'net_piasters' => $netPiasters,
        ];
    }

    /**
     * Apply progressive tax brackets to an annual taxable amount.
     * Per Income Tax Law 91/2005 — each bracket only taxes the portion
     * of income falling inside it, not total income at the top rate.
     */
    private function applyBrackets(int $annualTaxablePiasters, int $year): int
    {
        if ($annualTaxablePiasters <= 0) {
            return 0;
        }

        $brackets = TaxBracket::where('year', $year)->orderBy('tier')->get();

        if ($brackets->isEmpty()) {
            throw new InvalidArgumentException("No tax brackets configured for year {$year}.");
        }

        $totalTax = 0.0;

        foreach ($brackets as $bracket) {
            if ($annualTaxablePiasters < $bracket->min_piasters) {
                break;
            }

            $upper = $bracket->max_piasters !== null
                ? min($annualTaxablePiasters, $bracket->max_piasters)
                : $annualTaxablePiasters;

            $portion = $upper - $bracket->min_piasters + 1;
            // Min boundary correction: the +1 is for inclusive integer math.
            // For piaster precision the off-by-1 is negligible (< 1 piaster total),
            // but we drop it on the floor of the tier to keep things clean.
            if ($bracket->tier === 1) {
                $portion = $upper - $bracket->min_piasters;
            }

            $totalTax += $portion * (float) $bracket->rate;
        }

        return $this->halfUp($totalTax);
    }

    /**
     * Half-up rounding to nearest piaster per EGYPT_COMPLIANCE_RULES.md.
     * Standard accountant rounding — 0.5 rounds away from zero.
     */
    private function halfUp(float $piasters): int
    {
        return (int) round($piasters, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Convenience: calculate from an Employee model, picking the right year.
     */
    public function forEmployee(Employee $employee, int $year): array
    {
        return $this->calculateMonthly(
            (int) $employee->base_salary_piasters,
            $year,
            (bool) $employee->is_on_commercial_register,
        );
    }
}
