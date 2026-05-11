import AppLayout from '@/Layouts/AppLayout';
import EmptyState from '@/Components/EmptyState';
import { Head, Link, router } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { ReactNode, useState } from 'react';

type AttendanceRow = {
    id: number;
    type: 'check_in' | 'check_out';
    event_at: string | null;
    event_date: string | null;
    is_late: boolean;
    office: { id: number; name: string } | null;
    employee: { id: number; name: string } | null;
    verdict: 'verified' | 'possibly_self' | 'unverified' | 'bypassed';
    verdict_score: string | null;
    distance_meters: string | null;
};

type Paginated = {
    data: AttendanceRow[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Filters = {
    employee_id?: string | null;
    from?: string | null;
    to?: string | null;
    verdict?: string | null;
};

type Props = { records: Paginated; filters: Filters };

const VERDICT_STYLE: Record<string, string> = {
    verified: 'text-green-600',
    possibly_self: 'text-amber-500',
    unverified: 'text-red-500',
    bypassed: 'text-yzh-slate',
};

export default function Admin({ records, filters }: Props) {
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [verdict, setVerdict] = useState(filters.verdict ?? '');

    function applyFilters() {
        router.get('/admin/attendance', { from, to, verdict }, { preserveState: true });
    }

    return (
        <>
            <Head title="Attendance — Admin" />
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Filters</span>
                        </div>
                        <div className="flex flex-wrap items-end gap-4">
                            <div>
                                <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">From</label>
                                <input type="date" value={from} onChange={e => setFrom(e.target.value)} className="input-base h-9 min-h-0 py-1 text-xs" />
                            </div>
                            <div>
                                <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">To</label>
                                <input type="date" value={to} onChange={e => setTo(e.target.value)} className="input-base h-9 min-h-0 py-1 text-xs" />
                            </div>
                            <div>
                                <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Verdict</label>
                                <select value={verdict} onChange={e => setVerdict(e.target.value)} className="input-base h-9 min-h-0 py-1 text-xs w-40">
                                    <option value="">All</option>
                                    <option value="verified">Verified</option>
                                    <option value="possibly_self">Possibly Self</option>
                                    <option value="unverified">Unverified</option>
                                    <option value="bypassed">Bypassed</option>
                                </select>
                            </div>
                            <button onClick={applyFilters}
                                className="inline-flex min-h-9 h-9 items-center border border-yzh-gold px-4 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink">
                                Apply
                            </button>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">01</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                {records.total} record{records.total !== 1 ? 's' : ''}
                            </span>
                        </div>
                        {records.data.length === 0 ? (
                            <EmptyState
                                icon={Clock}
                                heading="No attendance records in scope"
                                description="Adjust filters or wait for employees to start checking in."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-yzh-bone-soft">
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Employee</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Date</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Time</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Type</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Office</th>
                                            <th className="py-2 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Verdict</th>
                                            <th className="py-2 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Late</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-yzh-bone-soft">
                                        {records.data.map(r => (
                                            <tr key={r.id}>
                                                <td className="py-2 pr-6 text-yzh-ink">{r.employee?.name ?? '—'}</td>
                                                <td className="py-2 pr-6 font-mono text-yzh-text">{r.event_date}</td>
                                                <td className="py-2 pr-6 font-mono text-yzh-text">
                                                    {r.event_at && new Date(r.event_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}
                                                </td>
                                                <td className="py-2 pr-6 text-yzh-ink capitalize">{r.type.replace('_', ' ')}</td>
                                                <td className="py-2 pr-6 text-yzh-slate">{r.office?.name ?? '—'}</td>
                                                <td className={`py-2 pr-6 font-mono text-[0.6875rem] uppercase tracking-[0.18em] ${VERDICT_STYLE[r.verdict]}`}>
                                                    {r.verdict.replace('_', ' ')}
                                                </td>
                                                <td className="py-2">
                                                    {r.is_late && <span className="font-mono text-[0.6rem] uppercase tracking-[0.18em] text-red-500">Late</span>}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </section>

                {records.last_page > 1 && (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5 flex items-center justify-between">
                            <span className="font-mono text-[0.6875rem] text-yzh-text">
                                {records.from}–{records.to} of {records.total}
                            </span>
                            <div className="flex gap-3">
                                {records.current_page > 1 && (
                                    <Link href={`/admin/attendance?page=${records.current_page - 1}`}
                                        className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-gold hover:text-yzh-ink">
                                        ← Prev
                                    </Link>
                                )}
                                {records.current_page < records.last_page && (
                                    <Link href={`/admin/attendance?page=${records.current_page + 1}`}
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

Admin.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.09 / Attendance / Admin</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Attendance Admin.</h1>
        </div>
    }>{page}</AppLayout>
);
