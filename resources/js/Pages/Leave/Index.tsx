import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { router } from '@inertiajs/react';
import { ReactNode, useState } from 'react';
import NewLeaveForm from './NewLeaveForm';

type LeaveType = {
    id: number;
    code: string;
    name: string;
    default_balance_days: number | null;
    requires_certificate_after_days: number | null;
    advance_notice_days: number | null;
    is_right_not_discretion: boolean;
    applies_to: 'all' | 'male' | 'female';
};

type LeaveRequest = {
    id: number;
    start_date: string;
    end_date: string;
    days_count: number;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    reason: string | null;
    rejected_reason: string | null;
    cancelled_at: string | null;
    leave_type: LeaveType | null;
};

type Paginated = {
    data: LeaveRequest[];
    current_page: number;
    last_page: number;
    total: number;
};

type Props = {
    tab: string;
    requests: Paginated;
    leaveTypes: LeaveType[];
};

const TABS = [
    { key: 'pending', label: 'Pending' },
    { key: 'approved', label: 'Approved' },
    { key: 'rejected', label: 'Rejected' },
];

function statusBadge(status: string) {
    const cls: Record<string, string> = {
        pending:   'text-yzh-text border-yzh-bone-soft',
        approved:  'text-yzh-gold border-yzh-gold',
        rejected:  'text-yzh-slate border-yzh-slate',
        cancelled: 'text-yzh-slate border-yzh-slate',
    };
    return `inline-flex items-center border px-2 py-0.5 font-mono text-[0.6rem] uppercase tracking-[0.18em] ${cls[status] ?? ''}`;
}

function RequestRow({ req }: { req: LeaveRequest }) {
    function cancel() {
        router.post(`/my-leave/${req.id}/cancel`, {}, { preserveScroll: true });
    }

    return (
        <div className="flex flex-wrap items-start justify-between gap-3 border-b border-yzh-bone-soft py-4 last:border-0">
            <div className="flex flex-col gap-1">
                <span className="text-sm font-medium text-yzh-ink">
                    {req.leave_type?.name ?? '–'}
                </span>
                <span className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                    {formatDate(req.start_date + 'T00:00:00')} –{' '}
                    {formatDate(req.end_date + 'T00:00:00')} · {req.days_count}{' '}
                    {req.days_count === 1 ? 'day' : 'days'}
                </span>
                {req.reason && <span className="text-xs text-yzh-slate">{req.reason}</span>}
                {req.rejected_reason && (
                    <span className="text-xs text-red-600">Reason: {req.rejected_reason}</span>
                )}
            </div>
            <div className="flex items-center gap-3">
                <span className={statusBadge(req.status)}>{req.status}</span>
                {req.status === 'pending' && (
                    <button
                        type="button"
                        onClick={cancel}
                        className="inline-flex min-h-[44px] items-center border border-yzh-bone-soft px-3 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-slate transition-colors hover:border-yzh-ink hover:text-yzh-ink"
                    >
                        Cancel
                    </button>
                )}
            </div>
        </div>
    );
}

function Index({ tab, requests, leaveTypes }: Props) {
    const [showForm, setShowForm] = useState(false);

    function navigate(newTab: string) {
        router.get('/my-leave', { tab: newTab }, { replace: true, preserveState: false });
    }

    return (
        <>
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-baseline gap-3">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                    00
                                </span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                    Leave requests / {requests.total} total
                                </span>
                            </div>
                            <button
                                type="button"
                                onClick={() => setShowForm((v) => !v)}
                                className="inline-flex min-h-[44px] items-center gap-3 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors hover:bg-yzh-gold hover:text-yzh-ink"
                            >
                                New request
                            </button>
                        </div>

                        <div className="mt-6 flex gap-0 border-b border-yzh-bone-soft">
                            {TABS.map((t) => (
                                <button
                                    key={t.key}
                                    type="button"
                                    onClick={() => navigate(t.key)}
                                    className={[
                                        'min-h-[44px] px-4 font-mono text-[0.6875rem] uppercase tracking-[0.18em] transition-colors',
                                        tab === t.key
                                            ? 'border-b-2 border-yzh-ink text-yzh-ink'
                                            : 'text-yzh-slate hover:text-yzh-ink',
                                    ].join(' ')}
                                >
                                    {t.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </section>

                {showForm && (
                    <section>
                        <NewLeaveForm
                            leaveTypes={leaveTypes}
                            onClose={() => setShowForm(false)}
                        />
                    </section>
                )}

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        {requests.data.length === 0 ? (
                            <p className="mt-4 text-sm text-yzh-slate">
                                No {tab} leave requests.
                            </p>
                        ) : (
                            <div>
                                {requests.data.map((req) => (
                                    <RequestRow key={req.id} req={req} />
                                ))}
                            </div>
                        )}

                        {requests.last_page > 1 && (
                            <div className="mt-6 flex gap-2">
                                {Array.from({ length: requests.last_page }, (_, i) => i + 1).map(
                                    (page) => (
                                        <button
                                            key={page}
                                            type="button"
                                            onClick={() =>
                                                router.get(
                                                    '/my-leave',
                                                    { tab, page },
                                                    { replace: true },
                                                )
                                            }
                                            className={[
                                                'min-h-[44px] min-w-[44px] border font-mono text-xs',
                                                requests.current_page === page
                                                    ? 'border-yzh-ink text-yzh-ink'
                                                    : 'border-yzh-bone-soft text-yzh-slate hover:border-yzh-ink',
                                            ].join(' ')}
                                        >
                                            {page}
                                        </button>
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    B.06 / Leave / My leave
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    My leave.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Index;
