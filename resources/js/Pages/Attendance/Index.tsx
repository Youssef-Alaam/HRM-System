import AppLayout from '@/Layouts/AppLayout';
import EmptyState from '@/Components/EmptyState';
import { Head } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { ReactNode } from 'react';

type AttendanceRow = {
    id: number;
    type: 'check_in' | 'check_out';
    event_at: string | null;
    event_date: string | null;
    is_late: boolean;
    office: { id: number; name: string } | null;
    verdict: 'verified' | 'possibly_self' | 'unverified' | 'bypassed';
    verdict_score: string | null;
    distance_meters: string | null;
    corrected_at: string | null;
};

type Paginated = {
    data: AttendanceRow[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
} | null;

type Props = { records: Paginated; has_employee: boolean };

const VERDICT_LABEL: Record<string, string> = {
    verified: 'Verified',
    possibly_self: 'Possibly Self',
    unverified: 'Unverified',
    bypassed: 'Bypassed',
};

const VERDICT_STYLE: Record<string, string> = {
    verified: 'text-green-600',
    possibly_self: 'text-amber-500',
    unverified: 'text-red-500',
    bypassed: 'text-yzh-slate',
};

export default function Index({ records, has_employee }: Props) {
    return (
        <>
            <Head title="Attendance" />
            <div className="space-y-12 sm:space-y-16">

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">About attendance</span>
                        </div>
                        <div className="rounded-sm border border-amber-200 bg-amber-50 p-4 max-w-2xl text-sm text-yzh-ink space-y-2">
                            <p>
                                <strong>Check-in / check-out UI is awaiting Walid's browser QA</strong> (Checkpoint B —
                                camera + geolocation thresholds + selfie retention sign-off).
                            </p>
                            <p>
                                The backend is live: <code className="font-mono text-xs">POST /attendance/check-in</code>
                                and <code className="font-mono text-xs">POST /attendance/check-out</code> accept lat/long,
                                accuracy, and a face-descriptor verdict score, and they enforce the office-radius +
                                Decision 7 verdict thresholds. Selfies are auto-purged 24h after capture via the
                                <code className="font-mono text-xs"> attendance:purge-selfies</code> hourly cron.
                            </p>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">01</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                {records?.total ?? 0} record{records?.total !== 1 ? 's' : ''}
                            </span>
                        </div>

                        {!has_employee ? (
                            <EmptyState
                                icon={Clock}
                                heading="Account not linked to an employee"
                                description="Ask HR to link your account to your employee record before you can record check-ins."
                            />
                        ) : !records || records.data.length === 0 ? (
                            <EmptyState
                                icon={Clock}
                                heading="No attendance recorded yet"
                                description="Once check-in goes live, your daily history will appear here."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-yzh-bone-soft">
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
                                                <td className="py-2 pr-6 text-yzh-ink font-mono">
                                                    {r.event_date && new Date(r.event_date).toLocaleDateString('en-GB', { dateStyle: 'medium' })}
                                                </td>
                                                <td className="py-2 pr-6 font-mono text-yzh-text">
                                                    {r.event_at && new Date(r.event_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}
                                                </td>
                                                <td className="py-2 pr-6 text-yzh-ink capitalize">{r.type.replace('_', ' ')}</td>
                                                <td className="py-2 pr-6 text-yzh-slate">{r.office?.name ?? '—'}</td>
                                                <td className={`py-2 pr-6 font-mono text-[0.6875rem] uppercase tracking-[0.18em] ${VERDICT_STYLE[r.verdict]}`}>
                                                    {VERDICT_LABEL[r.verdict]}
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
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.09 / Attendance</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Attendance.</h1>
        </div>
    }>{page}</AppLayout>
);
