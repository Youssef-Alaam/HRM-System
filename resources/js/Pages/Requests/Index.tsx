import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { PenSquare } from 'lucide-react';
import { ReactNode, useState } from 'react';

type RequestType = 'overtime' | 'expense_claim' | 'change_shift' | 'holiday_work';
type RequestStatus = 'pending' | 'approved' | 'rejected';

type RequestRow = {
    id: number;
    type: RequestType;
    status: RequestStatus;
    request_date: string;
    hours_requested: string | null;
    amount_piasters: number | null;
    notes: string | null;
    rejection_reason: string | null;
    approved_by: { name: string } | null;
    rejected_by: { name: string } | null;
    approved_at: string | null;
    created_at: string | null;
    employee?: { id: number; name: string; department: string | null } | null;
};

type Paginated = { data: RequestRow[]; current_page: number; last_page: number; total: number; from: number | null; to: number | null };

type Props = {
    mine: Paginated;
    pending: Paginated | null;
    tab: string;
    can_approve: boolean;
    has_employee: boolean;
};

const TYPE_LABELS: Record<RequestType, string> = {
    overtime: 'Overtime',
    expense_claim: 'Expense Claim',
    change_shift: 'Change Shift',
    holiday_work: 'Holiday Work',
};

const STATUS_STYLES: Record<RequestStatus, string> = {
    pending: 'text-yzh-slate',
    approved: 'text-green-600',
    rejected: 'text-red-600',
};

function fmtEGP(piasters: number | null): string {
    if (piasters === null) return '—';
    return `EGP ${(piasters / 100).toLocaleString('en-EG', { minimumFractionDigits: 2 })}`;
}

export default function Index({ mine, pending, tab, can_approve, has_employee }: Props) {
    const [composing, setComposing] = useState(false);
    const [rejectId, setRejectId] = useState<number | null>(null);
    const rejectForm = useForm({ rejection_reason: '' });

    const form = useForm({
        type: 'overtime' as RequestType,
        request_date: '',
        notes: '',
        hours_requested: '',
        amount_piasters: '',
    });

    function submitRequest(e: React.FormEvent) {
        e.preventDefault();
        form.post('/requests', { onSuccess: () => { setComposing(false); form.reset(); } });
    }

    function handleApprove(id: number) {
        router.post(`/requests/${id}/approve`);
    }

    function handleReject(e: React.FormEvent) {
        e.preventDefault();
        if (rejectId === null) return;
        rejectForm.post(`/requests/${rejectId}/reject`, {
            onSuccess: () => { setRejectId(null); rejectForm.reset(); },
        });
    }

    const list = tab === 'pending' ? (pending?.data ?? []) : mine.data;
    const paginator = tab === 'pending' ? pending : mine;

    return (
        <>
            <Head title="My Requests" />
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex flex-wrap items-baseline justify-between gap-3">
                            <div className="flex items-baseline gap-4">
                                <TabLink href="/requests" active={tab === 'mine'} label="My Requests" />
                                {can_approve && (
                                    <TabLink href="/requests?tab=pending" active={tab === 'pending'} label="Pending approval" />
                                )}
                            </div>
                            {has_employee && tab === 'mine' && (
                                <button onClick={() => setComposing(v => !v)}
                                    className="inline-flex min-h-11 items-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink">
                                    <PenSquare className="h-3.5 w-3.5" /> New request
                                </button>
                            )}
                        </div>
                    </div>
                </section>

                {composing && has_employee && tab === 'mine' && (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5">
                            <div className="flex items-baseline gap-3 mb-6">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">New request</span>
                            </div>
                            <form onSubmit={submitRequest} className="max-w-lg space-y-4">
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Type</label>
                                    <select value={form.data.type}
                                        onChange={e => form.setData('type', e.target.value as RequestType)}
                                        className="input-base">
                                        {(Object.entries(TYPE_LABELS) as [RequestType, string][]).map(([v, l]) => (
                                            <option key={v} value={v}>{l}</option>
                                        ))}
                                    </select>
                                    {form.errors.type && <p className="mt-1 text-xs text-red-600">{form.errors.type}</p>}
                                </div>
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Date</label>
                                    <input type="date" value={form.data.request_date}
                                        onChange={e => form.setData('request_date', e.target.value)}
                                        className="input-base" />
                                    {form.errors.request_date && <p className="mt-1 text-xs text-red-600">{form.errors.request_date}</p>}
                                </div>
                                {form.data.type === 'overtime' && (
                                    <div>
                                        <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Hours (max 2 per Art. 85)</label>
                                        <input type="number" min="0.25" max="2" step="0.25"
                                            value={form.data.hours_requested}
                                            onChange={e => form.setData('hours_requested', e.target.value)}
                                            className="input-base" />
                                        {form.errors.hours_requested && <p className="mt-1 text-xs text-red-600">{form.errors.hours_requested}</p>}
                                    </div>
                                )}
                                {form.data.type === 'expense_claim' && (
                                    <div>
                                        <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Amount (EGP)</label>
                                        <input type="number" min="0.01" step="0.01"
                                            placeholder="0.00"
                                            value={form.data.amount_piasters ? String(Number(form.data.amount_piasters) / 100) : ''}
                                            onChange={e => form.setData('amount_piasters', e.target.value ? String(Math.round(Number(e.target.value) * 100)) : '')}
                                            className="input-base" />
                                        {form.errors.amount_piasters && <p className="mt-1 text-xs text-red-600">{form.errors.amount_piasters}</p>}
                                    </div>
                                )}
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Notes</label>
                                    <textarea value={form.data.notes}
                                        onChange={e => form.setData('notes', e.target.value)}
                                        rows={3} className="input-base resize-none" />
                                    {form.errors.notes && <p className="mt-1 text-xs text-red-600">{form.errors.notes}</p>}
                                </div>
                                <button type="submit" disabled={form.processing}
                                    className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                                    Submit
                                </button>
                            </form>
                        </div>
                    </section>
                )}

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">{composing ? '01' : '00'}</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                {paginator?.total ?? 0} {tab === 'pending' ? 'pending' : 'request'}{(paginator?.total ?? 0) !== 1 ? 's' : ''}
                            </span>
                        </div>
                        {list.length === 0 ? (
                            <p className="text-sm text-yzh-slate">{tab === 'pending' ? 'No pending requests.' : 'No requests yet.'}</p>
                        ) : (
                            <ul className="divide-y divide-yzh-bone-soft">
                                {list.map(r => (
                                    <li key={r.id} className="py-4">
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-3 mb-1">
                                                    <span className="font-semibold text-yzh-ink">{TYPE_LABELS[r.type]}</span>
                                                    <span className={`font-mono text-[0.6875rem] uppercase tracking-[0.18em] ${STATUS_STYLES[r.status]}`}>
                                                        {r.status}
                                                    </span>
                                                </div>
                                                {tab === 'pending' && r.employee && (
                                                    <p className="text-sm text-yzh-slate mb-0.5">{r.employee.name}{r.employee.department ? ` · ${r.employee.department}` : ''}</p>
                                                )}
                                                <p className="text-sm text-yzh-text">
                                                    {new Date(r.request_date).toLocaleDateString('en-GB', { dateStyle: 'medium' })}
                                                    {r.hours_requested && ` · ${r.hours_requested} hrs`}
                                                    {r.amount_piasters != null && ` · ${fmtEGP(r.amount_piasters)}`}
                                                    {r.notes && ` · ${r.notes.slice(0, 80)}`}
                                                </p>
                                                {r.rejection_reason && (
                                                    <p className="mt-1 text-xs text-red-500">Rejected: {r.rejection_reason}</p>
                                                )}
                                            </div>
                                            <div className="flex shrink-0 items-center gap-2">
                                                <span className="font-mono text-[0.6875rem] text-yzh-text shrink-0">
                                                    {r.created_at ? new Date(r.created_at).toLocaleDateString('en-GB') : ''}
                                                </span>
                                                {tab === 'pending' && r.status === 'pending' && (
                                                    <>
                                                        <button onClick={() => handleApprove(r.id)}
                                                            className="inline-flex min-h-9 items-center bg-yzh-gold px-3 py-1.5 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-ink hover:bg-yzh-gold/90">
                                                            Approve
                                                        </button>
                                                        <button onClick={() => setRejectId(r.id)}
                                                            className="inline-flex min-h-9 items-center border border-red-200 px-3 py-1.5 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-red-500 hover:bg-red-50">
                                                            Reject
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                        {rejectId === r.id && (
                                            <form onSubmit={handleReject} className="mt-3 flex gap-3">
                                                <input value={rejectForm.data.rejection_reason}
                                                    onChange={e => rejectForm.setData('rejection_reason', e.target.value)}
                                                    placeholder="Reason for rejection…"
                                                    className="input-base flex-1 h-9 min-h-0 py-1.5 text-xs" />
                                                <button type="submit" disabled={rejectForm.processing}
                                                    className="inline-flex min-h-9 items-center border border-red-300 px-3 py-1.5 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-red-500 hover:bg-red-50 disabled:opacity-50">
                                                    Confirm
                                                </button>
                                                <button type="button" onClick={() => setRejectId(null)}
                                                    className="inline-flex min-h-9 items-center border border-yzh-bone-soft px-3 py-1.5 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text hover:text-yzh-ink">
                                                    Cancel
                                                </button>
                                            </form>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>

                {paginator && paginator.last_page > 1 && (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5 flex items-center justify-between">
                            <span className="font-mono text-[0.6875rem] text-yzh-text">
                                {paginator.from}–{paginator.to} of {paginator.total}
                            </span>
                            <div className="flex gap-3">
                                {paginator.current_page > 1 && (
                                    <Link href={`/requests?tab=${tab}&page=${paginator.current_page - 1}`}
                                        className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-gold hover:text-yzh-ink">
                                        ← Prev
                                    </Link>
                                )}
                                {paginator.current_page < paginator.last_page && (
                                    <Link href={`/requests?tab=${tab}&page=${paginator.current_page + 1}`}
                                        className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-gold hover:text-yzh-ink">
                                        Next →
                                    </Link>
                                )}
                            </div>
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

function TabLink({ href, active, label }: { href: string; active: boolean; label: string }) {
    return (
        <Link href={href} className={`font-mono text-xs uppercase tracking-[0.22em] transition-colors ${active ? 'text-yzh-gold' : 'text-yzh-text hover:text-yzh-ink'}`}>
            {label}
        </Link>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.03 / Requests</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">My Requests.</h1>
        </div>
    }>{page}</AppLayout>
);
