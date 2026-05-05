import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { ReactNode, useState } from 'react';

type DeptRow = { department: string | null; total: number };
type StatusRow = { employment_status: string; total: number };
type BalanceRow = { id: number; name: string; department: string | null; annual: number; sick: number; casual: number };
type OwnBalance = { annual: number; sick: number; casual: number } | null;
type DocRow = { id: number; name: string; passport_expiry: string | null; work_permit_expiry: string | null; residency_permit_expiry: string | null };
type OvertimeRow = { name: string; department: string | null; total_hours: number; requests_count: number };

type Props = {
    can_any: boolean;
    can_team: boolean;
    from: string;
    to: string;
    own_balance: OwnBalance;
    headcount_by_dept: DeptRow[] | null;
    headcount_by_status: StatusRow[] | null;
    leave_balances: BalanceRow[] | null;
    expiring_docs: DocRow[] | null;
    overtime_summary: OvertimeRow[] | null;
};

function Section({ num, title, children }: { num: string; title: string; children: React.ReactNode }) {
    return (
        <section>
            <div className="border-t border-yzh-bone-soft pt-5">
                <div className="flex items-baseline gap-3 mb-6">
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">{num}</span>
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">{title}</span>
                </div>
                {children}
            </div>
        </section>
    );
}

function fmtDate(d: string | null) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { dateStyle: 'medium' });
}

export default function Index({ can_any, can_team, from, to, own_balance, headcount_by_dept, headcount_by_status, leave_balances, expiring_docs, overtime_summary }: Props) {
    const [localFrom, setLocalFrom] = useState(from);
    const [localTo, setLocalTo] = useState(to);

    function applyDateFilter() {
        router.get('/reports', { from: localFrom, to: localTo }, { preserveState: true });
    }

    let sectionIdx = 0;
    const nextNum = () => String(sectionIdx++).padStart(2, '0');

    return (
        <>
            <Head title="Reports" />
            <div className="space-y-12 sm:space-y-16">

                {/* Own balance card — all roles */}
                {own_balance && (
                    <Section num={nextNum()} title="My leave balances">
                        <div className="grid grid-cols-3 gap-6 max-w-lg">
                            {([['Annual', own_balance.annual], ['Sick', own_balance.sick], ['Casual', own_balance.casual]] as [string, number][]).map(([label, val]) => (
                                <div key={label} className="border border-yzh-bone-soft p-4">
                                    <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text mb-1">{label}</p>
                                    <p className="text-2xl font-semibold text-yzh-ink">{val}<span className="ml-1 text-sm text-yzh-slate font-normal">days</span></p>
                                </div>
                            ))}
                        </div>
                    </Section>
                )}

                {/* Headcount by department — managers + HR */}
                {headcount_by_dept && (
                    <Section num={nextNum()} title="Headcount by department">
                        <table className="w-full max-w-lg text-sm">
                            <thead>
                                <tr className="border-b border-yzh-bone-soft">
                                    <th className="py-2 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Department</th>
                                    <th className="py-2 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Active</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-yzh-bone-soft">
                                {headcount_by_dept.map(r => (
                                    <tr key={r.department ?? 'unassigned'}>
                                        <td className="py-2 text-yzh-ink">{r.department ?? 'Unassigned'}</td>
                                        <td className="py-2 text-right font-mono text-yzh-ink">{r.total}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </Section>
                )}

                {/* Headcount by status — HR/Admin only */}
                {headcount_by_status && (
                    <Section num={nextNum()} title="Headcount by status">
                        <table className="w-full max-w-sm text-sm">
                            <thead>
                                <tr className="border-b border-yzh-bone-soft">
                                    <th className="py-2 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Status</th>
                                    <th className="py-2 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Count</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-yzh-bone-soft">
                                {headcount_by_status.map(r => (
                                    <tr key={r.employment_status}>
                                        <td className="py-2 text-yzh-ink capitalize">{r.employment_status.replace('_', ' ')}</td>
                                        <td className="py-2 text-right font-mono text-yzh-ink">{r.total}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </Section>
                )}

                {/* Leave balances — managers/HR see their team/all */}
                {leave_balances && (
                    <Section num={nextNum()} title={can_any ? 'Leave balances — all employees' : 'Leave balances — my team'}>
                        {leave_balances.length === 0 ? (
                            <p className="text-sm text-yzh-slate">No active employees in scope.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-yzh-bone-soft">
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Employee</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Dept</th>
                                            <th className="py-2 pr-4 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Annual</th>
                                            <th className="py-2 pr-4 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Sick</th>
                                            <th className="py-2 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Casual</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-yzh-bone-soft">
                                        {leave_balances.map(r => (
                                            <tr key={r.id}>
                                                <td className="py-2 pr-6 text-yzh-ink">{r.name}</td>
                                                <td className="py-2 pr-6 text-yzh-slate">{r.department ?? '—'}</td>
                                                <td className="py-2 pr-4 text-right font-mono text-yzh-ink">{r.annual}</td>
                                                <td className="py-2 pr-4 text-right font-mono text-yzh-ink">{r.sick}</td>
                                                <td className="py-2 text-right font-mono text-yzh-ink">{r.casual}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Section>
                )}

                {/* Overtime summary — managers/HR with date filter */}
                {overtime_summary !== null && (
                    <Section num={nextNum()} title="Approved overtime">
                        <div className="mb-4 flex items-end gap-4">
                            <div>
                                <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">From</label>
                                <input type="date" value={localFrom} onChange={e => setLocalFrom(e.target.value)} className="input-base h-9 min-h-0 py-1.5 text-xs" />
                            </div>
                            <div>
                                <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">To</label>
                                <input type="date" value={localTo} onChange={e => setLocalTo(e.target.value)} className="input-base h-9 min-h-0 py-1.5 text-xs" />
                            </div>
                            <button onClick={applyDateFilter}
                                className="inline-flex min-h-9 h-9 items-center border border-yzh-gold px-4 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink">
                                Apply
                            </button>
                        </div>
                        {overtime_summary.length === 0 ? (
                            <p className="text-sm text-yzh-slate">No approved overtime in this period.</p>
                        ) : (
                            <table className="w-full max-w-2xl text-sm">
                                <thead>
                                    <tr className="border-b border-yzh-bone-soft">
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Employee</th>
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Dept</th>
                                        <th className="py-2 pr-4 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Hours</th>
                                        <th className="py-2 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Requests</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-yzh-bone-soft">
                                    {overtime_summary.map((r, i) => (
                                        <tr key={i}>
                                            <td className="py-2 pr-6 text-yzh-ink">{r.name}</td>
                                            <td className="py-2 pr-6 text-yzh-slate">{r.department ?? '—'}</td>
                                            <td className="py-2 pr-4 text-right font-mono text-yzh-ink">{r.total_hours.toFixed(1)}</td>
                                            <td className="py-2 text-right font-mono text-yzh-ink">{r.requests_count}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </Section>
                )}

                {/* Expiring documents — HR/Admin only */}
                {expiring_docs !== null && (
                    <Section num={nextNum()} title="Expiring documents (next 60 days)">
                        {expiring_docs.length === 0 ? (
                            <p className="text-sm text-yzh-slate">No documents expiring in the next 60 days.</p>
                        ) : (
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-yzh-bone-soft">
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Employee</th>
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Passport</th>
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Work Permit</th>
                                        <th className="py-2 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Residency</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-yzh-bone-soft">
                                    {expiring_docs.map(r => (
                                        <tr key={r.id}>
                                            <td className="py-2 pr-6 text-yzh-ink">{r.name}</td>
                                            <td className={`py-2 pr-6 font-mono text-sm ${r.passport_expiry ? 'text-red-500' : 'text-yzh-slate'}`}>{fmtDate(r.passport_expiry)}</td>
                                            <td className={`py-2 pr-6 font-mono text-sm ${r.work_permit_expiry ? 'text-red-500' : 'text-yzh-slate'}`}>{fmtDate(r.work_permit_expiry)}</td>
                                            <td className={`py-2 font-mono text-sm ${r.residency_permit_expiry ? 'text-red-500' : 'text-yzh-slate'}`}>{fmtDate(r.residency_permit_expiry)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </Section>
                )}
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.04 / Reports</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Reports.</h1>
        </div>
    }>{page}</AppLayout>
);
