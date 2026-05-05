import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, Clock } from 'lucide-react';
import { ReactNode, useState } from 'react';

type ProbationRow = {
    id: number;
    name: string;
    department: string | null;
    position: string | null;
    contract_start_date: string;
    probation_ends: string;
    days_remaining: number;
    alert_level: 'ok' | 'warning' | 'overdue';
};

type RetirementRow = {
    id: number;
    name: string;
    date_of_birth: string;
    retirement_date: string;
    days_until_retirement: number;
};

type DeemedRow = {
    id: number;
    name: string;
    department: string | null;
    unauthorized_absences_ytd: number;
};

type Props = {
    probation: ProbationRow[];
    retirement: RetirementRow[];
    deemed: DeemedRow[];
};

function AlertBadge({ level }: { level: ProbationRow['alert_level'] }) {
    if (level === 'overdue') return <span className="inline-flex items-center gap-1 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-red-500"><AlertTriangle className="h-3 w-3" />Overdue</span>;
    if (level === 'warning') return <span className="inline-flex items-center gap-1 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-amber-500"><Clock className="h-3 w-3" />Expiring soon</span>;
    return <span className="inline-flex items-center gap-1 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-slate"><CheckCircle className="h-3 w-3" />Active</span>;
}

function Section({ num, title, badge, children }: { num: string; title: string; badge?: number; children: React.ReactNode }) {
    return (
        <section>
            <div className="border-t border-yzh-bone-soft pt-5">
                <div className="flex items-baseline gap-3 mb-6">
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">{num}</span>
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">{title}</span>
                    {badge !== undefined && badge > 0 && (
                        <span className="ml-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-yzh-gold px-1 font-mono text-[0.6rem] text-yzh-ink">{badge}</span>
                    )}
                </div>
                {children}
            </div>
        </section>
    );
}

export default function Index({ probation, retirement, deemed }: Props) {
    const [terminatingId, setTerminatingId] = useState<number | null>(null);
    const [extendingId, setExtendingId] = useState<number | null>(null);
    const terminateForm = useForm({ reason: 'termination' as string, effective_date: '', override_protection: false });
    const extendForm = useForm({ until: '' });

    function handleTerminate(e: React.FormEvent) {
        e.preventDefault();
        if (!terminatingId) return;
        terminateForm.post(`/compliance/employees/${terminatingId}/terminate`, {
            onSuccess: () => { setTerminatingId(null); terminateForm.reset(); },
        });
    }

    function handleExtend(e: React.FormEvent) {
        e.preventDefault();
        if (!extendingId) return;
        extendForm.post(`/compliance/employees/${extendingId}/extend-retirement`, {
            onSuccess: () => { setExtendingId(null); extendForm.reset(); },
        });
    }

    return (
        <>
            <Head title="Compliance Workflows" />
            <div className="space-y-12 sm:space-y-16">

                <Section num="00" title="Probation expiry" badge={probation.filter(p => p.alert_level !== 'ok').length}>
                    {probation.length === 0 ? (
                        <p className="text-sm text-yzh-slate">No employees on probation.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-yzh-bone-soft">
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Employee</th>
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Dept</th>
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Probation ends</th>
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Days</th>
                                        <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Status</th>
                                        <th className="py-2 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-yzh-bone-soft">
                                    {probation.map(p => (
                                        <tr key={p.id}>
                                            <td className="py-2 pr-6 text-yzh-ink">{p.name}</td>
                                            <td className="py-2 pr-6 text-yzh-slate">{p.department ?? '—'}</td>
                                            <td className="py-2 pr-6 font-mono text-yzh-text">
                                                {new Date(p.probation_ends).toLocaleDateString('en-GB', { dateStyle: 'medium' })}
                                            </td>
                                            <td className="py-2 pr-6 font-mono text-yzh-ink">{Math.max(0, p.days_remaining)}</td>
                                            <td className="py-2 pr-6"><AlertBadge level={p.alert_level} /></td>
                                            <td className="py-2">
                                                <button onClick={() => setTerminatingId(p.id)}
                                                    className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-red-500 hover:text-red-700">
                                                    Terminate
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Section>

                <Section num="01" title="Approaching retirement" badge={retirement.length}>
                    {retirement.length === 0 ? (
                        <p className="text-sm text-yzh-slate">No employees approaching retirement age in the next 6 months.</p>
                    ) : (
                        <table className="w-full max-w-2xl text-sm">
                            <thead>
                                <tr className="border-b border-yzh-bone-soft">
                                    <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Employee</th>
                                    <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Retirement date</th>
                                    <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Days</th>
                                    <th className="py-2 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-yzh-bone-soft">
                                {retirement.map(r => (
                                    <tr key={r.id}>
                                        <td className="py-2 pr-6 text-yzh-ink">{r.name}</td>
                                        <td className="py-2 pr-6 font-mono text-yzh-text">
                                            {new Date(r.retirement_date).toLocaleDateString('en-GB', { dateStyle: 'medium' })}
                                        </td>
                                        <td className="py-2 pr-6 font-mono text-yzh-ink">{r.days_until_retirement}</td>
                                        <td className="py-2 flex gap-2">
                                            <button onClick={() => { setTerminatingId(r.id); terminateForm.setData('reason', 'retirement'); }}
                                                className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-gold hover:text-yzh-ink">
                                                Retire
                                            </button>
                                            <button onClick={() => setExtendingId(r.id)}
                                                className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text hover:text-yzh-ink">
                                                Extend
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </Section>

                <Section num="02" title="Deemed resignation candidates (Art. 69)" badge={deemed.length}>
                    {deemed.length === 0 ? (
                        <p className="text-sm text-yzh-slate">No employees flagged for deemed resignation.</p>
                    ) : (
                        <table className="w-full max-w-2xl text-sm">
                            <thead>
                                <tr className="border-b border-yzh-bone-soft">
                                    <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Employee</th>
                                    <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Dept</th>
                                    <th className="py-2 pr-6 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Absences YTD</th>
                                    <th className="py-2 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-yzh-bone-soft">
                                {deemed.map(d => (
                                    <tr key={d.id}>
                                        <td className="py-2 pr-6 text-yzh-ink">{d.name}</td>
                                        <td className="py-2 pr-6 text-yzh-slate">{d.department ?? '—'}</td>
                                        <td className="py-2 pr-6 text-right font-mono text-red-500">{d.unauthorized_absences_ytd}</td>
                                        <td className="py-2">
                                            <button onClick={() => { setTerminatingId(d.id); terminateForm.setData('reason', 'deemed_resignation'); }}
                                                className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-red-500 hover:text-red-700">
                                                Confirm resignation
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </Section>

                {/* Termination form modal */}
                {terminatingId !== null && (
                    <Section num="03" title="Initiate termination">
                        <form onSubmit={handleTerminate} className="max-w-md space-y-4">
                            <div>
                                <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Reason</label>
                                <select value={terminateForm.data.reason}
                                    onChange={e => terminateForm.setData('reason', e.target.value)}
                                    className="input-base">
                                    <option value="resignation">Resignation</option>
                                    <option value="dismissal">Dismissal</option>
                                    <option value="deemed_resignation">Deemed resignation</option>
                                    <option value="retirement">Retirement</option>
                                    <option value="end_of_contract">End of contract</option>
                                    <option value="mutual_agreement">Mutual agreement</option>
                                    <option value="probation_termination">Probation termination</option>
                                </select>
                                {terminateForm.errors.reason && <p className="mt-1 text-xs text-red-600">{terminateForm.errors.reason}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Effective date (leave blank for notice period default)</label>
                                <input type="date" value={terminateForm.data.effective_date}
                                    onChange={e => terminateForm.setData('effective_date', e.target.value)}
                                    className="input-base" />
                            </div>
                            <p className="text-xs text-yzh-slate">EOSB and notice period will be computed automatically per Labor Law Art. 110-115.</p>
                            <div className="flex gap-3">
                                <button type="submit" disabled={terminateForm.processing}
                                    className="inline-flex min-h-11 items-center border border-red-300 px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-red-500 hover:bg-red-50 disabled:opacity-50">
                                    Confirm termination
                                </button>
                                <button type="button" onClick={() => setTerminatingId(null)}
                                    className="inline-flex min-h-11 items-center border border-yzh-bone-soft px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-text hover:text-yzh-ink">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </Section>
                )}

                {/* Extend retirement form */}
                {extendingId !== null && (
                    <Section num="04" title="Extend retirement">
                        <form onSubmit={handleExtend} className="max-w-md space-y-4">
                            <div>
                                <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Extend until</label>
                                <input type="date" value={extendForm.data.until}
                                    onChange={e => extendForm.setData('until', e.target.value)}
                                    className="input-base" />
                                {extendForm.errors.until && <p className="mt-1 text-xs text-red-600">{extendForm.errors.until}</p>}
                            </div>
                            <p className="text-xs text-yzh-slate">Per Social Insurance Law 148/2019 §11 — extension requires mutual agreement.</p>
                            <div className="flex gap-3">
                                <button type="submit" disabled={extendForm.processing}
                                    className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                                    Save extension
                                </button>
                                <button type="button" onClick={() => setExtendingId(null)}
                                    className="inline-flex min-h-11 items-center border border-yzh-bone-soft px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-text hover:text-yzh-ink">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </Section>
                )}
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.05 / Compliance</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Compliance Workflows.</h1>
        </div>
    }>{page}</AppLayout>
);
