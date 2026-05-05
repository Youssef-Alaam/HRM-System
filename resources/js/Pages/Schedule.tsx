import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { ReactNode } from 'react';

type ScheduleDay = {
    date: string;
    day_name: string;
    is_workday: boolean;
    is_today: boolean;
    holiday: { id: number; name: string } | null;
};

type EmployeeData = {
    shift_start: string | null;
    shift_end: string | null;
    workweek_days: string[];
};

type Props = {
    employee: EmployeeData | null;
    week_start: string;
    week_end: string;
    days: ScheduleDay[];
    prev_week: string;
    next_week: string;
};

function navigate(week: string) {
    router.get('/schedule', { week }, { replace: true, preserveState: true, preserveScroll: true });
}

function fmt12(time: string | null): string {
    if (!time) return '–';
    const [h, m] = time.split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour = h % 12 || 12;
    return `${hour}:${String(m).padStart(2, '0')} ${period}`;
}

function WeekGrid({ days }: { days: ScheduleDay[] }) {
    return (
        <div className="overflow-x-auto pb-2">
            <div className="grid min-w-[42rem] grid-cols-7 gap-px border border-yzh-bone-soft bg-yzh-bone-soft">
                {days.map((day) => (
                    <div
                        key={day.date}
                        className={[
                            'flex min-h-[9rem] flex-col gap-1 bg-white p-3',
                            day.is_today
                                ? 'ring-2 ring-inset ring-yzh-ink'
                                : '',
                            !day.is_workday && !day.is_today
                                ? 'bg-yzh-bone-soft/30'
                                : '',
                        ]
                            .filter(Boolean)
                            .join(' ')}
                    >
                        <div className="flex flex-col gap-0.5">
                            <span
                                className={[
                                    'font-mono text-[0.6rem] uppercase tracking-[0.2em]',
                                    day.is_today
                                        ? 'text-yzh-ink font-semibold'
                                        : 'text-yzh-text',
                                ]
                                    .filter(Boolean)
                                    .join(' ')}
                            >
                                {day.day_name}
                            </span>
                            <span
                                className={[
                                    'text-sm font-medium',
                                    day.is_today
                                        ? 'text-yzh-ink'
                                        : 'text-yzh-slate',
                                ]
                                    .filter(Boolean)
                                    .join(' ')}
                            >
                                {new Date(day.date + 'T00:00:00').getDate()}
                            </span>
                        </div>

                        {day.holiday && (
                            <span className="mt-1 border-l-2 border-yzh-gold pl-1.5 font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-gold">
                                {day.holiday.name}
                            </span>
                        )}

                        {day.is_workday && (
                            <span className="mt-auto font-mono text-[0.6rem] uppercase tracking-[0.12em] text-yzh-text">
                                Work
                            </span>
                        )}
                        {!day.is_workday && (
                            <span className="mt-auto font-mono text-[0.6rem] uppercase tracking-[0.12em] text-yzh-slate/50">
                                Off
                            </span>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

function Schedule({ employee, week_start, week_end, days, prev_week, next_week }: Props) {
    const weekLabel = `${formatDate(week_start + 'T00:00:00')} – ${formatDate(week_end + 'T00:00:00')}`;

    return (
        <>
            <div className="space-y-12 sm:space-y-16">
                {/* 00 — Shift */}
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                00
                            </span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Today's shift
                            </span>
                        </div>

                        {employee ? (
                            <div className="mt-6 flex flex-wrap items-start gap-8">
                                <div className="flex flex-col gap-1">
                                    <span className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                                        Start
                                    </span>
                                    <span className="text-2xl font-semibold tracking-tight text-yzh-ink">
                                        {fmt12(employee.shift_start)}
                                    </span>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <span className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                                        End
                                    </span>
                                    <span className="text-2xl font-semibold tracking-tight text-yzh-ink">
                                        {fmt12(employee.shift_end)}
                                    </span>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <span className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                                        Hours today
                                    </span>
                                    <span className="font-mono text-2xl font-semibold tracking-tight text-yzh-slate">
                                        –:–:–
                                    </span>
                                </div>
                            </div>
                        ) : (
                            <p className="mt-6 text-sm text-yzh-slate">
                                No schedule configured. Contact HR to assign a shift.
                            </p>
                        )}

                        <div className="mt-8">
                            <button
                                type="button"
                                disabled
                                className="inline-flex min-h-[44px] cursor-not-allowed items-center gap-3 border border-yzh-bone-soft bg-yzh-bone-soft/60 px-6 py-2.5 font-mono text-xs uppercase tracking-[0.22em] text-yzh-slate"
                                aria-disabled="true"
                            >
                                Sign In — Coming with Attendance (Feature 5)
                            </button>
                        </div>
                    </div>
                </section>

                {/* 01 — Week */}
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-baseline gap-3">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                    01
                                </span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                    Week / {weekLabel}
                                </span>
                            </div>

                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => navigate(prev_week)}
                                    className="inline-flex min-h-[44px] min-w-[44px] items-center justify-center border border-yzh-bone-soft text-yzh-slate transition-colors hover:border-yzh-ink hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                                    aria-label="Previous week"
                                >
                                    <ChevronLeft size={16} />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => navigate(next_week)}
                                    className="inline-flex min-h-[44px] min-w-[44px] items-center justify-center border border-yzh-bone-soft text-yzh-slate transition-colors hover:border-yzh-ink hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                                    aria-label="Next week"
                                >
                                    <ChevronRight size={16} />
                                </button>
                            </div>
                        </div>

                        <div className="mt-6">
                            <WeekGrid days={days} />
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

Schedule.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    B.04 / Time / My schedule
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    My schedule.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Schedule;
