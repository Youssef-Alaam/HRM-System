<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollSetting;
use App\Models\TaxBracket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only viewer for tax brackets + payroll constants.
 * Editing pre-Checkpoint-D is intentionally not exposed — these values are
 * the accountant's responsibility. Display only, with a clear callout that
 * Payroll v1 (Feature 15) is awaiting banking + accountant onboarding.
 */
class PayrollRatesController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('settings.edit'), 403);

        $year = (int) $request->query('year', now()->year);

        $brackets = TaxBracket::where('year', $year)
            ->orderBy('tier')
            ->get()
            ->map(fn ($b) => [
                'tier' => $b->tier,
                'min_egp' => $b->min_piasters / 100,
                'max_egp' => $b->max_piasters !== null ? $b->max_piasters / 100 : null,
                'rate_pct' => (float) $b->rate * 100,
            ])->values();

        $settings = PayrollSetting::where('year', $year)->first();
        $payload = $settings ? [
            'personal_allowance_egp' => $settings->personal_allowance_piasters / 100,
            'si_insurable_floor_egp' => $settings->si_insurable_floor_piasters / 100,
            'si_insurable_cap_egp' => $settings->si_insurable_cap_piasters / 100,
            'si_employee_pct' => (float) $settings->si_employee_rate * 100,
            'si_employer_pct' => (float) $settings->si_employer_rate * 100,
            'health_employee_pct' => (float) $settings->health_employee_rate * 100,
            'health_employer_pct' => (float) $settings->health_employer_rate * 100,
            'training_employer_pct' => (float) $settings->training_employer_rate * 100,
            'minimum_wage_egp' => $settings->minimum_wage_piasters / 100,
        ] : null;

        $availableYears = TaxBracket::query()->select('year')->distinct()->orderByDesc('year')->pluck('year');

        return Inertia::render('Admin/PayrollRates', [
            'year' => $year,
            'brackets' => $brackets,
            'settings' => $payload,
            'available_years' => $availableYears,
        ]);
    }
}
