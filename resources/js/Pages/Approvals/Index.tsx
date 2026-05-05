import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { router } from '@inertiajs/react';
import { ReactNode, useState } from 'react';

type LeaveType = {
    id: number;
    code: string;
    name: string;
    is_right_not_discretion: boolean;
};

type EmployeeSummary = {
    id: number;
    name: string;
    department: string | null;
};

type ApprovalRequest = {
    id: number;
    start_date: string;
    end_date: string;
    days_count: number;
    status: 'pending';
    reason: string | null;
    leave_type: LeaveType | null;
    employee: EmployeeSummary | null;
};

type Paginated = {
    data: ApprovalRequest[];
    current_page: number;
    last_page: number;
    total: number;
};

type Props = {
    requests: Paginated;
    role: 'manager' | 'hr';
};

function RequestRow({ req, role }: { req: ApprovalRequest; role: 'manager' | 'hr' }) {
    const [showReject, setShowReject] = useState(false);
    const [reason, setReason] = useState('');

    const isRightsBased = req.leave_type?.is_right_not_discretion ?? false;
    const canReject = role === 'hr' || !isRightsBased;

    function approve() {
        router.post(`/approvals/${req.id}/approve`, {}, { preserveScroll: true });
    }

    function submitReject(e: React.FormEvent) {
        e.preventDefault();
        router.post(`/approvals/${req.id}/reject`, { rejected_reason: reason }, { preserveScroll: true });
        setShowReject(false);
        setReason('');
    }

    return (
        <div className="border-b border-yzh-bone-soft py-5 last:border-0">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex flex-col gap-1">
                    <span className="text-sm font-semibold text-yzh-ink">
                        {req.employee?.name ?? '–'}
                    </span>
                    <span className="font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-slate">
                        {req.employee?.department ?? '–'}
                    </span>
                    <span className="mt-1 text-sm text-yzh-text">
                        {req.leave_type?.name ?? '–'}
                    </span>
                    <span className="font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-text">
                        {formatDate(req.start_date + 'T00:00:00')} –{' '}
                        {formatDate(req.end_date + 'T00:00:00')} · {req.days_count}{' '}
                        {req.days_count === 1 ? 'day' : 'days'}
                    </span>
                    {req.reason && (
                        <span className="text-xs text-yzh-slate">{req.reason}</span>
                    )}
                    {isRightsBased && (
                        <span className="font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-gold">
                            Legal right
                        </span>
                    )}
                </div>

                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={approve}
                        className="inline-flex min-h-[44px] items-center border border-yzh-gold px-4 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-gold transition-colors hover:bg-yzh-gold hover:text-yzh-ink"
                    >
                        Approve
                    </button>

                    {canReject ? (
                        <button
                            type="button"
                            onClick={() => setShowReject((v) => !v)}
                            className="inline-flex min-h-[44px] items-center border border-yzh-bone-soft px-4 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-slate transition-colors hover:border-yzh-ink hover:text-yzh-ink"
                        >
                            Reject
                        </button>
                    ) : (
                        <span
                            title="Rights-based leave cannot be rejected by managers. Escalate to HR."
                            className="inline-flex min-h-[44px] cursor-not-allowed items-center border border-yzh-bone-soft px-4 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-bone-soft"
                        >
                            Reject
                        </span>
                    )}
                </div>
            </div>

            {showReject && (
                <form onSubmit={submitReject} className="mt-4 max-w-sm space-y-3">
                    <div className="flex flex-col gap-1">
                        <label className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                            Reason for rejection
                        </label>
                        <textarea
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            rows={2}
                            required
                            minLength={5}
                            className="border border-yzh-bone-soft px-3 py-2 text-sm text-yzh-ink focus:border-yzh-ink focus:outline-none"
                        />
                    </div>
                    <div className="flex gap-2">
                        <button
                            type="submit"
                            className="inline-flex min-h-[44px] items-center border border-yzh-slate px-4 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-slate transition-colors hover:border-yzh-ink hover:text-yzh-ink"
                        >
                            Confirm reject
                        </button>
                        <button
                            type="button"
                            onClick={() => setShowReject(false)}
                            className="inline-flex min-h-[44px] items-center border border-yzh-bone-soft px-4 font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-slate transition-colors hover:text-yzh-ink"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}

function Index({ requests, role }: Props) {
    return (
        <>
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                00
                            </span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Pending approvals / {requests.total} total
                            </span>
                        </div>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        {requests.data.length === 0 ? (
                            <p className="mt-4 text-sm text-yzh-slate">No pending leave requests.</p>
                        ) : (
                            <div>
                                {requests.data.map((req) => (
                                    <RequestRow key={req.id} req={req} role={role} />
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
                                                router.get('/approvals', { page }, { replace: true })
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
                    B.07 / Leave / Approvals
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Approvals.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Index;
