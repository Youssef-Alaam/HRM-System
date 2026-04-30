import Skeleton from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';
import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { ReactNode } from 'react';

type LeaveBalance = {
    annual: number;
    sick: number;
    casual: number;
};

type Headcount = {
    total: number;
};

type DepartmentRow = { name: string; count: number };

type LoginRow = {
    name: string;
    email: string;
    at: string;
    ip: string | null;
};

type SystemMetrics = {
    users: number;
    audit_entries: number;
    holidays: number;
};

type Widgets = {
    identity: { name: string; email: string; role: string };
    leave_balance?: LeaveBalance;
    headcount?: Headcount;
    department_breakdown?: DepartmentRow[];
    recent_logins?: LoginRow[];
    system_metrics?: SystemMetrics;
};

type Props = {
    widgets: Widgets;
    loading?: boolean;
};

const UPCOMING_MODULES = [
    { section: '01', name: 'Attendance', detail: 'GPS + selfie check-in with face verification.', status: 'Soon' },
    { section: '02', name: 'Leave', detail: 'Egyptian-law balances, approvals, calendar.', status: 'Soon' },
    { section: '03', name: 'Employees', detail: 'Roster, profiles, contracts, expat documents.', status: 'Soon' },
    { section: '04', name: 'Org chart', detail: 'Department hierarchy, manager delegation.', status: 'Soon' },
    { section: '05', name: 'Payroll', detail: 'Tax + SI + health insurance, expat handling, EOSB.', status: 'Phase 2' },
];

function DashboardHeader() {
    const user = usePage().props.auth.user;
    const firstName = user.name.split(' ')[0];
    const today = formatDate(new Date());

    return (
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                Today / {today}
            </p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                Hello, {firstName}.
            </h1>
        </div>
    );
}

function Dashboard({ widgets, loading = false }: Props) {
    // Headcount polls every 60s (Decision 27). Only fires for roles that
    // received the headcount widget. Pauses when tab is hidden.
    useEffect(() => {
        if (!widgets.headcount) return;
        let interval: ReturnType<typeof setInterval> | null = null;
        const start = () => {
            if (interval) return;
            interval = setInterval(() => {
                router.reload({ only: ['widgets'] });
            }, 60_000);
        };
        const stop = () => {
            if (interval) clearInterval(interval);
            interval = null;
        };
        const onVisibility = () => {
            if (document.hidden) {
                stop();
            } else {
                start();
            }
        };
        start();
        document.addEventListener('visibilitychange', onVisibility);
        return () => {
            stop();
            document.removeEventListener('visibilitychange', onVisibility);
        };
    }, [widgets.headcount]);

    let n = 0;
    const nextSection = () => String(n++).padStart(2, '0');

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-12 sm:space-y-16">
                <Section number={nextSection()} label="Status">
                    <h2 className="mt-3 text-2xl font-semibold leading-tight tracking-tight text-yzh-ink">
                        Foundation phase in progress.
                    </h2>
                    <p className="mt-3 max-w-xl text-sm leading-relaxed text-yzh-slate">
                        The infrastructure is being built. Feature screens
                        (attendance, leave, payroll) come online as each
                        feature ships.
                    </p>
                </Section>

                {widgets.headcount && (
                    <Section number={nextSection()} label="Headcount">
                        <HeadcountWidget
                            headcount={widgets.headcount}
                            loading={loading}
                        />
                    </Section>
                )}

                {widgets.department_breakdown && (
                    <Section number={nextSection()} label="Departments">
                        <DepartmentBreakdown
                            rows={widgets.department_breakdown}
                            loading={loading}
                        />
                    </Section>
                )}

                {widgets.leave_balance && (
                    <Section number={nextSection()} label="Leave balance">
                        <LeaveBalanceWidget
                            balance={widgets.leave_balance}
                            loading={loading}
                        />
                    </Section>
                )}

                {widgets.system_metrics && (
                    <Section number={nextSection()} label="System">
                        <SystemMetricsWidget
                            metrics={widgets.system_metrics}
                            loading={loading}
                        />
                    </Section>
                )}

                {widgets.recent_logins && (
                    <Section number={nextSection()} label="Recent logins">
                        <RecentLoginsWidget
                            logins={widgets.recent_logins}
                            loading={loading}
                        />
                    </Section>
                )}

                <Section number={nextSection()} label="Drawing index">
                    <ul className="mt-6 divide-y divide-yzh-bone-soft">
                        {loading
                            ? Array.from({ length: 4 }).map((_, i) => (
                                  <li key={i} className="py-5">
                                      <ModuleRowSkeleton />
                                  </li>
                              ))
                            : UPCOMING_MODULES.map((m) => (
                                  <li
                                      key={m.section}
                                      className="grid grid-cols-12 gap-4 py-5 sm:gap-6"
                                  >
                                      <span className="col-span-2 font-mono text-xs uppercase tracking-[0.24em] text-yzh-text sm:col-span-1">
                                          {m.section}
                                      </span>
                                      <h3 className="col-span-10 text-base font-semibold text-yzh-ink sm:col-span-3">
                                          {m.name}
                                      </h3>
                                      <p className="col-span-12 text-sm leading-relaxed text-yzh-slate sm:col-span-6">
                                          {m.detail}
                                      </p>
                                      <span className="col-span-12 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold sm:col-span-2 sm:text-right">
                                          {m.status}
                                      </span>
                                  </li>
                              ))}
                    </ul>
                </Section>
            </div>
        </>
    );
}

function Section({
    number,
    label,
    children,
}: {
    number: string;
    label: string;
    children: ReactNode;
}) {
    return (
        <section>
            <div className="border-t border-yzh-bone-soft pt-5">
                <div className="flex items-baseline gap-3">
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                        {number}
                    </span>
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                        {label}
                    </span>
                </div>
                {children}
            </div>
        </section>
    );
}

function HeadcountWidget({
    headcount,
    loading,
}: {
    headcount: Headcount;
    loading: boolean;
}) {
    if (loading) {
        return <Skeleton className="mt-3 h-16 w-32 rounded" />;
    }
    return (
        <div className="mt-4 flex items-baseline gap-4">
            <span className="text-5xl font-semibold tracking-tight text-yzh-ink sm:text-6xl">
                {headcount.total}
            </span>
            <span className="font-mono text-xs uppercase tracking-[0.22em] text-yzh-text">
                Active employees
            </span>
        </div>
    );
}

function DepartmentBreakdown({
    rows,
    loading,
}: {
    rows: DepartmentRow[];
    loading: boolean;
}) {
    if (loading) {
        return (
            <div className="mt-6 space-y-3">
                {Array.from({ length: 4 }).map((_, i) => (
                    <Skeleton key={i} className="h-4 w-full rounded" />
                ))}
            </div>
        );
    }
    if (rows.length === 0) {
        return (
            <p className="mt-3 text-sm text-yzh-slate">
                No departments yet. Create one in Settings.
            </p>
        );
    }
    return (
        <ul className="mt-6 divide-y divide-yzh-bone-soft">
            {rows.map((row) => (
                <li
                    key={row.name}
                    className="flex items-center justify-between py-3"
                >
                    <span className="text-sm font-semibold text-yzh-ink">
                        {row.name}
                    </span>
                    <span className="font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold">
                        {row.count} active
                    </span>
                </li>
            ))}
        </ul>
    );
}

function LeaveBalanceWidget({
    balance,
    loading,
}: {
    balance: LeaveBalance;
    loading: boolean;
}) {
    if (loading) {
        return <Skeleton className="mt-3 h-12 w-full rounded" />;
    }
    return (
        <dl className="mt-6 grid grid-cols-3 gap-6">
            <BalanceCell label="Annual" days={balance.annual} />
            <BalanceCell label="Sick" days={balance.sick} />
            <BalanceCell label="Casual" days={balance.casual} />
        </dl>
    );
}

function BalanceCell({ label, days }: { label: string; days: number }) {
    return (
        <div className="border-l border-yzh-bone-soft pl-4">
            <dt className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                {label}
            </dt>
            <dd className="mt-1 text-2xl font-semibold tracking-tight text-yzh-ink">
                {days}
                <span className="ml-1 text-sm font-normal text-yzh-slate">
                    days
                </span>
            </dd>
        </div>
    );
}

function SystemMetricsWidget({
    metrics,
    loading,
}: {
    metrics: SystemMetrics;
    loading: boolean;
}) {
    if (loading) {
        return <Skeleton className="mt-3 h-12 w-full rounded" />;
    }
    return (
        <dl className="mt-6 grid grid-cols-3 gap-6">
            <BalanceCell label="Users" days={metrics.users} />
            <BalanceCell label="Audit rows" days={metrics.audit_entries} />
            <BalanceCell label="Holidays" days={metrics.holidays} />
        </dl>
    );
}

function RecentLoginsWidget({
    logins,
    loading,
}: {
    logins: LoginRow[];
    loading: boolean;
}) {
    if (loading) {
        return (
            <div className="mt-6 space-y-3">
                {Array.from({ length: 5 }).map((_, i) => (
                    <Skeleton key={i} className="h-4 w-full rounded" />
                ))}
            </div>
        );
    }
    if (logins.length === 0) {
        return (
            <p className="mt-3 text-sm text-yzh-slate">
                No login events recorded yet.
            </p>
        );
    }
    return (
        <ul className="mt-6 divide-y divide-yzh-bone-soft">
            {logins.map((row, i) => (
                <li
                    key={i}
                    className="grid grid-cols-12 gap-3 py-3 sm:gap-6"
                >
                    <span className="col-span-12 truncate text-sm font-semibold text-yzh-ink sm:col-span-4">
                        {row.name}
                    </span>
                    <span className="col-span-12 truncate font-mono text-xs text-yzh-slate sm:col-span-4">
                        {row.email}
                    </span>
                    <span className="col-span-12 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text sm:col-span-2">
                        {row.ip ?? '—'}
                    </span>
                    <time className="col-span-12 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text sm:col-span-2 sm:text-right">
                        {new Date(row.at).toLocaleString('en-GB', {
                            day: '2-digit',
                            month: 'short',
                            hour: '2-digit',
                            minute: '2-digit',
                        })}
                    </time>
                </li>
            ))}
        </ul>
    );
}

function ModuleRowSkeleton() {
    return (
        <div className="grid grid-cols-12 gap-4 sm:gap-6">
            <Skeleton className="col-span-2 h-3 rounded sm:col-span-1" />
            <Skeleton className="col-span-10 h-4 w-32 rounded sm:col-span-3" />
            <Skeleton className="col-span-12 h-3 rounded sm:col-span-6" />
            <Skeleton className="col-span-12 h-3 w-16 rounded sm:col-span-2 sm:justify-self-end" />
        </div>
    );
}

Dashboard.layout = (page: ReactNode) => (
    <AppLayout header={<DashboardHeader />}>{page}</AppLayout>
);

export default Dashboard;
