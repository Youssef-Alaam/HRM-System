import AppLayout from '@/Layouts/AppLayout';
import { Head, usePage } from '@inertiajs/react';
import {
    CalendarClock,
    ClipboardList,
    GitBranch,
    Sparkles,
    Users,
} from 'lucide-react';
import { ReactNode } from 'react';

function DashboardHeader() {
    const user = usePage().props.auth.user;
    const firstName = user.name.split(' ')[0];
    const today = new Date().toLocaleDateString('en-GB', {
        weekday: 'long',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

    return (
        <div className="flex flex-col gap-1">
            <p className="text-xs uppercase tracking-widest text-yzh-text">{today}</p>
            <h1 className="text-2xl font-semibold tracking-tight text-yzh-ink">
                Hello, {firstName}.
            </h1>
        </div>
    );
}

function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-8">
                {/* Welcome card */}
                <section className="rounded-lg border border-yzh-bone-soft bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full border border-yzh-gold/30 bg-yzh-gold-50 px-3 py-1 text-xs font-medium uppercase tracking-widest text-yzh-gold-700">
                                <Sparkles className="h-3 w-3" />
                                Welcome to YZH HR
                            </div>
                            <h2 className="mt-3 text-lg font-semibold text-yzh-ink">
                                Foundation phase in progress
                            </h2>
                            <p className="mt-1 text-sm text-yzh-slate">
                                The infrastructure is being built. Feature
                                screens (attendance, leave, payroll) come
                                online once the foundation is approved.
                            </p>
                        </div>
                    </div>
                </section>

                {/* What's coming */}
                <section>
                    <h2 className="mb-4 text-xs font-medium uppercase tracking-widest text-yzh-text">
                        Coming up
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <ComingCard
                            icon={CalendarClock}
                            label="Attendance"
                            detail="GPS + selfie check-in with face verification."
                        />
                        <ComingCard
                            icon={ClipboardList}
                            label="Leave"
                            detail="Egyptian-law balances, approvals, calendar."
                        />
                        <ComingCard
                            icon={Users}
                            label="Employees"
                            detail="Roster, profiles, contracts, expat documents."
                        />
                        <ComingCard
                            icon={GitBranch}
                            label="Org chart"
                            detail="Department hierarchy, manager delegation."
                        />
                    </div>
                </section>
            </div>
        </>
    );
}

Dashboard.layout = (page: ReactNode) => (
    <AppLayout header={<DashboardHeader />}>{page}</AppLayout>
);

export default Dashboard;

function ComingCard({
    icon: Icon,
    label,
    detail,
}: {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    detail: string;
}) {
    return (
        <div className="rounded-lg border border-yzh-bone-soft bg-white p-5 shadow-sm transition-colors hover:border-yzh-gold/40">
            <div className="flex h-10 w-10 items-center justify-center rounded-md bg-yzh-gold/10 text-yzh-gold">
                <Icon className="h-5 w-5" />
            </div>
            <h3 className="mt-4 text-sm font-semibold text-yzh-ink">{label}</h3>
            <p className="mt-1 text-xs text-yzh-slate">{detail}</p>
            <p className="mt-3 inline-flex items-center text-[10px] uppercase tracking-widest text-yzh-text">
                Soon
            </p>
        </div>
    );
}
