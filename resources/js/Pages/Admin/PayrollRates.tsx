import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { ReactNode, useState } from 'react';

type Bracket = {
    tier: number;
    min_egp: number;
    max_egp: number | null;
    rate_pct: number;
};

type Settings = {
    personal_allowance_egp: number;
    si_insurable_floor_egp: number;
    si_insurable_cap_egp: number;
    si_employee_pct: number;
    si_employer_pct: number;
    health_employee_pct: number;
    health_employer_pct: number;
    training_employer_pct: number;
    minimum_wage_egp: number;
} | null;

type Props = {
    year: number;
    brackets: Bracket[];
    settings: Settings;
    available_years: number[];
};

function fmtEGP(v: number): string {
    return new Intl.NumberFormat('en-EG', { maximumFractionDigits: 0 }).format(v);
}

export default function PayrollRates({ year, brackets, settings, available_years }: Props) {
    const [yearInput, setYearInput] = useState(year.toString());

    function applyYear() {
        router.get('/admin/payroll-rates', { year: yearInput }, { preserveState: true });
    }

    return (
        <>
            <Head title={`Payroll Rates — ${year}`} />
            <div className="space-y-12 sm:space-y-16">

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex flex-wrap items-baseline justify-between gap-3 mb-6">
                            <div className="flex items-baseline gap-3">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Year</span>
                            </div>
                            <div className="flex items-end gap-3">
                                <select value={yearInput} onChange={e => setYearInput(e.target.value)}
                                    className="input-base h-9 min-h-0 py-1 text-xs w-28">
                                    {available_years.length === 0
                                        ? <option value={year}>{year}</option>
                                        : available_years.map(y => <option key={y} value={y}>{y}</option>)}
                                </select>
                                <button onClick={applyYear}
                                    className="inline-flex min-h-9 h-9 items-center border border-yzh-gold px-4 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink">
                                    Load
                                </button>
                            </div>
                        </div>

                        <div className="rounded-sm border border-amber-200 bg-amber-50 p-4 max-w-2xl">
                            <p className="text-sm text-yzh-ink">
                                <strong>Read-only preview.</strong> Payroll v1 (Feature 15) is awaiting banking partner
                                + Egyptian payroll-specialist accountant onboarding. These rates ship at Checkpoint D
                                with accountant sign-off. Edit through database seeder until then.
                            </p>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">01</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Income tax brackets — Law 91/2005</span>
                        </div>
                        {brackets.length === 0 ? (
                            <p className="text-sm text-yzh-slate">No brackets configured for {year}. Run <code className="font-mono text-xs">php artisan db:seed --class=PayrollRatesSeeder</code>.</p>
                        ) : (
                            <table className="w-full max-w-xl text-sm">
                                <thead>
                                    <tr className="border-b border-yzh-bone-soft">
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Tier</th>
                                        <th className="py-2 pr-6 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">From (EGP/year)</th>
                                        <th className="py-2 pr-6 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">To (EGP/year)</th>
                                        <th className="py-2 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Rate</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-yzh-bone-soft">
                                    {brackets.map(b => (
                                        <tr key={b.tier}>
                                            <td className="py-2 pr-6 text-yzh-ink font-mono">{b.tier}</td>
                                            <td className="py-2 pr-6 text-right font-mono text-yzh-ink">{fmtEGP(b.min_egp)}</td>
                                            <td className="py-2 pr-6 text-right font-mono text-yzh-ink">
                                                {b.max_egp !== null ? fmtEGP(b.max_egp) : '∞'}
                                            </td>
                                            <td className="py-2 text-right font-mono text-yzh-gold">{b.rate_pct}%</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </section>

                {settings && (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5">
                            <div className="flex items-baseline gap-3 mb-6">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">02</span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Constants — Law 148/2019 + Art. 12</span>
                            </div>
                            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-12 gap-y-3 max-w-2xl text-sm">
                                <Row label="Personal allowance (annual, tax-free)" value={`EGP ${fmtEGP(settings.personal_allowance_egp)}`} />
                                <Row label="Minimum wage (monthly)" value={`EGP ${fmtEGP(settings.minimum_wage_egp)}`} />
                                <Row label="SI insurable floor (monthly)" value={`EGP ${fmtEGP(settings.si_insurable_floor_egp)}`} />
                                <Row label="SI insurable cap (monthly)" value={`EGP ${fmtEGP(settings.si_insurable_cap_egp)}`} />
                                <Row label="SI rate — employee" value={`${settings.si_employee_pct}%`} />
                                <Row label="SI rate — employer" value={`${settings.si_employer_pct}%`} />
                                <Row label="Health insurance — employee" value={`${settings.health_employee_pct}%`} />
                                <Row label="Health insurance — employer" value={`${settings.health_employer_pct}%`} />
                                <Row label="Training fund — employer" value={`${settings.training_employer_pct}%`} />
                                <Row label="Total employer SI cost"
                                    value={`${(settings.si_employer_pct + settings.health_employer_pct + settings.training_employer_pct).toFixed(2)}%`} />
                            </dl>
                        </div>
                    </section>
                )}

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">03</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Calculation flow</span>
                        </div>
                        <ol className="max-w-xl list-decimal list-inside text-sm text-yzh-ink space-y-2">
                            <li>Insurable wage = clamp(monthly salary, floor, cap)</li>
                            <li>Employee SI deduction = insurable × (SI emp % + health emp %)</li>
                            <li>Annual taxable = (monthly × 12) − personal allowance − (annual employee SI)</li>
                            <li>Apply progressive brackets to annual taxable → annual tax</li>
                            <li>Monthly tax withholding = annual tax / 12 (half-up rounding)</li>
                            <li>Net pay = gross − employee SI − monthly tax</li>
                        </ol>
                    </div>
                </section>
            </div>
        </>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <>
            <dt className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">{label}</dt>
            <dd className="font-mono text-yzh-ink">{value}</dd>
        </>
    );
}

PayrollRates.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.08 / Settings / Payroll Rates</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Payroll Rates.</h1>
        </div>
    }>{page}</AppLayout>
);
