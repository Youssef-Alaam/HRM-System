import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, XCircle } from 'lucide-react';
import { ReactNode } from 'react';

type Quota = {
    total_employees: number;
    expat_employees: number;
    local_employees: number;
    headcount_ratio: number;
    headcount_limit: number;
    headcount_ok: boolean;
    total_salary_piasters: number;
    expat_salary_piasters: number;
    payroll_ratio: number;
    payroll_limit: number;
    payroll_ok: boolean;
    status: 'green' | 'yellow' | 'red';
};

type ExpatRow = {
    id: number;
    name: string;
    nationality: string | null;
    department: string | null;
    position: string | null;
    work_permit_expiry: string | null;
    passport_expiry: string | null;
    base_salary_piasters: number;
};

type Props = { quota: Quota; expats: ExpatRow[] };

function StatusIcon({ ok }: { ok: boolean }) {
    if (ok) return <CheckCircle className="h-4 w-4 text-green-500" />;
    return <XCircle className="h-4 w-4 text-red-500" />;
}

function GaugeBar({ value, limit, ok }: { value: number; limit: number; ok: boolean }) {
    const pct = Math.min((value / limit) * 100, 100);
    return (
        <div className="mt-2 h-2 w-full rounded-full bg-yzh-bone-soft overflow-hidden">
            <div
                className={`h-full rounded-full transition-all ${ok ? 'bg-green-400' : 'bg-red-500'}`}
                style={{ width: `${pct}%` }}
            />
        </div>
    );
}

function fmtEGP(piasters: number): string {
    return `EGP ${(piasters / 100).toLocaleString('en-EG', { minimumFractionDigits: 0 })}`;
}

export default function Index({ quota, expats }: Props) {
    const statusBg = quota.status === 'green'
        ? 'bg-green-50 border-green-200'
        : quota.status === 'yellow'
        ? 'bg-amber-50 border-amber-200'
        : 'bg-red-50 border-red-200';

    return (
        <>
            <Head title="Foreign Worker Quota" />
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Quota status — Labor Law Art. 17</span>
                        </div>

                        {quota.status !== 'green' && (
                            <div className={`mb-6 flex items-start gap-3 rounded-sm border p-4 ${statusBg}`}>
                                <AlertTriangle className="h-4 w-4 shrink-0 mt-0.5 text-amber-500" />
                                <p className="text-sm text-yzh-ink">
                                    {quota.status === 'red'
                                        ? 'Quota breached. New expat hires are blocked until the ratio returns below the legal limit.'
                                        : 'Quota approaching limit. Review before hiring additional expats.'}
                                </p>
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 max-w-2xl">
                            <div className="border border-yzh-bone-soft p-5">
                                <div className="flex items-center justify-between mb-1">
                                    <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Headcount quota</p>
                                    <StatusIcon ok={quota.headcount_ok} />
                                </div>
                                <p className="text-2xl font-semibold text-yzh-ink">
                                    {quota.headcount_ratio.toFixed(1)}%
                                    <span className="ml-2 text-sm font-normal text-yzh-slate">/ {quota.headcount_limit}% limit</span>
                                </p>
                                <GaugeBar value={quota.headcount_ratio} limit={quota.headcount_limit} ok={quota.headcount_ok} />
                                <p className="mt-2 text-xs text-yzh-slate">{quota.expat_employees} expats / {quota.total_employees} total employees</p>
                            </div>

                            <div className="border border-yzh-bone-soft p-5">
                                <div className="flex items-center justify-between mb-1">
                                    <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Payroll quota</p>
                                    <StatusIcon ok={quota.payroll_ok} />
                                </div>
                                <p className="text-2xl font-semibold text-yzh-ink">
                                    {quota.payroll_ratio.toFixed(1)}%
                                    <span className="ml-2 text-sm font-normal text-yzh-slate">/ {quota.payroll_limit}% limit</span>
                                </p>
                                <GaugeBar value={quota.payroll_ratio} limit={quota.payroll_limit} ok={quota.payroll_ok} />
                                <p className="mt-2 text-xs text-yzh-slate">
                                    {fmtEGP(quota.expat_salary_piasters)} expat / {fmtEGP(quota.total_salary_piasters)} total (base salaries)
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">01</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                {expats.length} active expat{expats.length !== 1 ? 's' : ''}
                            </span>
                        </div>
                        {expats.length === 0 ? (
                            <p className="text-sm text-yzh-slate">No active expat employees.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-yzh-bone-soft">
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Name</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Nationality</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Dept</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Work permit exp.</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Passport exp.</th>
                                            <th className="py-2 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Base salary</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-yzh-bone-soft">
                                        {expats.map(e => {
                                            const wpExpiring = e.work_permit_expiry && new Date(e.work_permit_expiry) <= new Date(Date.now() + 60 * 86400 * 1000);
                                            const ppExpiring = e.passport_expiry && new Date(e.passport_expiry) <= new Date(Date.now() + 60 * 86400 * 1000);
                                            return (
                                                <tr key={e.id}>
                                                    <td className="py-2 pr-6 text-yzh-ink">
                                                        <Link href={`/employees/${e.id}`} className="hover:text-yzh-gold">{e.name}</Link>
                                                    </td>
                                                    <td className="py-2 pr-6 text-yzh-slate">{e.nationality ?? '—'}</td>
                                                    <td className="py-2 pr-6 text-yzh-slate">{e.department ?? '—'}</td>
                                                    <td className={`py-2 pr-6 font-mono text-xs ${wpExpiring ? 'text-red-500' : 'text-yzh-text'}`}>
                                                        {e.work_permit_expiry ? new Date(e.work_permit_expiry).toLocaleDateString('en-GB', { dateStyle: 'medium' }) : '—'}
                                                        {wpExpiring && ' ⚠'}
                                                    </td>
                                                    <td className={`py-2 pr-6 font-mono text-xs ${ppExpiring ? 'text-red-500' : 'text-yzh-text'}`}>
                                                        {e.passport_expiry ? new Date(e.passport_expiry).toLocaleDateString('en-GB', { dateStyle: 'medium' }) : '—'}
                                                        {ppExpiring && ' ⚠'}
                                                    </td>
                                                    <td className="py-2 text-right font-mono text-yzh-ink">
                                                        {fmtEGP(e.base_salary_piasters)}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.07 / Foreign Quota</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Foreign Worker Quota.</h1>
        </div>
    }>{page}</AppLayout>
);
