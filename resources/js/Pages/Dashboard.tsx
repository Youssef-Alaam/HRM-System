import Skeleton from '@/Components/Skeleton';
import { formatDate } from '@/lib/format';
import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';

type Module = {
    section: string;
    name: string;
    detail: string;
    status: string;
};

const MODULES: Module[] = [
    {
        section: '01',
        name: 'Attendance',
        detail: 'GPS + selfie check-in with face verification.',
        status: 'Soon',
    },
    {
        section: '02',
        name: 'Leave',
        detail: 'Egyptian-law balances, approvals, calendar.',
        status: 'Soon',
    },
    {
        section: '03',
        name: 'Employees',
        detail: 'Roster, profiles, contracts, expat documents.',
        status: 'Soon',
    },
    {
        section: '04',
        name: 'Org chart',
        detail: 'Department hierarchy, manager delegation.',
        status: 'Soon',
    },
    {
        section: '05',
        name: 'Payroll',
        detail: 'Tax + SI + health insurance, expat handling, EOSB.',
        status: 'Phase 2',
    },
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

function Dashboard({ loading = false }: { loading?: boolean }) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-12 sm:space-y-16">
                {/* 00 / Status — replaces the welcome card */}
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                00
                            </span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Status
                            </span>
                        </div>
                        <h2 className="mt-3 text-2xl font-semibold leading-tight tracking-tight text-yzh-ink">
                            Foundation phase in progress.
                        </h2>
                        <p className="mt-3 max-w-xl text-sm leading-relaxed text-yzh-slate">
                            The infrastructure is being built. Feature screens
                            (attendance, leave, payroll) come online once the
                            foundation is approved.
                        </p>
                    </div>
                </section>

                {/* 01 / Drawing index — replaces the card grid */}
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                01
                            </span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Drawing index
                            </span>
                        </div>

                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {loading
                                ? Array.from({ length: 4 }).map((_, i) => (
                                      <li key={i} className="py-5">
                                          <ModuleRowSkeleton />
                                      </li>
                                  ))
                                : MODULES.map((m) => (
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
                    </div>
                </section>
            </div>
        </>
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
