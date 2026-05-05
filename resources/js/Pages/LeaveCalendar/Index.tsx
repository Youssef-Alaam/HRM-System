import AppLayout from '@/Layouts/AppLayout';
import { router } from '@inertiajs/react';
import { ReactNode, useState } from 'react';

type CalendarEntry = {
    id: number;
    employee_id: number;
    employee_name: string;
    leave_type_code: string | null;
    leave_type_name: string | null;
    leave_type_color: string;
    start_date: string;
    end_date: string;
    days_count: number;
};

type HolidayEntry = {
    date: string;
    name: string;
};

type DayCell = {
    date: string;
    is_today: boolean;
    is_weekend: boolean;
};

type Props = {
    view: 'month' | 'week';
    year: number;
    month: number;
    week: number | null;
    week_start: string;
    week_end: string;
    days: DayCell[];
    entries: CalendarEntry[];
    holidays: HolidayEntry[];
    role: 'employee' | 'manager' | 'hr';
};

const COLOR_CLASSES: Record<string, { bar: string; label: string }> = {
    gold: { bar: 'border-yzh-gold bg-yzh-gold/10', label: 'text-yzh-gold' },
    slate: { bar: 'border-yzh-slate bg-yzh-slate/5', label: 'text-yzh-slate' },
    teal: { bar: 'border-teal-400 bg-teal-50', label: 'text-teal-700' },
    amber: { bar: 'border-amber-400 bg-amber-50', label: 'text-amber-700' },
    default: { bar: 'border-yzh-bone-soft bg-yzh-bone-soft/30', label: 'text-yzh-slate' },
};

const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

function EntryBar({ entry }: { entry: CalendarEntry }) {
    const [open, setOpen] = useState(false);
    const colors = COLOR_CLASSES[entry.leave_type_color] ?? COLOR_CLASSES.default;

    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className={`w-full truncate border-l-2 px-1 py-0.5 text-left ${colors.bar}`}
            >
                <span className={`block truncate font-mono text-[0.55rem] uppercase tracking-[0.12em] ${colors.label}`}>
                    {entry.employee_name}
                    {entry.leave_type_name ? ` · ${entry.leave_type_name}` : ' · Out of office'}
                </span>
            </button>
            {open && (
                <div className="absolute left-0 top-full z-20 min-w-[180px] border border-yzh-bone-soft bg-white p-3 shadow-sm">
                    <p className="text-xs font-semibold text-yzh-ink">{entry.employee_name}</p>
                    <p className="mt-1 font-mono text-[0.6rem] uppercase tracking-[0.14em] text-yzh-text">
                        {entry.leave_type_name ?? 'Out of office'}
                    </p>
                    <p className="mt-1 font-mono text-[0.6rem] text-yzh-slate">
                        {entry.start_date} – {entry.end_date}
                    </p>
                    <p className="font-mono text-[0.6rem] text-yzh-slate">{entry.days_count} day(s)</p>
                    <button
                        type="button"
                        onClick={() => setOpen(false)}
                        className="mt-2 font-mono text-[0.6rem] uppercase tracking-[0.14em] text-yzh-slate hover:text-yzh-ink"
                    >
                        Close
                    </button>
                </div>
            )}
        </div>
    );
}

function DayGrid({ days, entries, holidays }: { days: DayCell[]; entries: CalendarEntry[]; holidays: HolidayEntry[] }) {
    const holidayMap = new Map(holidays.map((h) => [h.date, h.name]));

    function entriesForDay(date: string): CalendarEntry[] {
        return entries.filter((e) => e.start_date <= date && e.end_date >= date);
    }

    return (
        <div className="overflow-x-auto">
            <div className="grid min-w-[560px]" style={{ gridTemplateColumns: `repeat(${days.length > 7 ? 7 : days.length}, minmax(0,1fr))` }}>
                {/* Day header row */}
                {days.slice(0, days.length > 7 ? 7 : days.length).map((d) => (
                    <div
                        key={`hdr-${d.date}`}
                        className="border-b border-yzh-bone-soft pb-1 text-center font-mono text-[0.6rem] uppercase tracking-[0.14em] text-yzh-slate"
                    >
                        {new Date(d.date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short' })}
                    </div>
                ))}

                {/* Day cells */}
                {days.map((day) => {
                    const holiday = holidayMap.get(day.date);
                    const dayEntries = entriesForDay(day.date);

                    return (
                        <div
                            key={day.date}
                            className={[
                                'min-h-[80px] border-b border-r border-yzh-bone-soft p-1',
                                day.is_today ? 'bg-yzh-gold/5 ring-1 ring-inset ring-yzh-gold' : '',
                                day.is_weekend ? 'bg-yzh-bone-soft/20' : '',
                            ].join(' ')}
                        >
                            <span
                                className={`block text-right font-mono text-[0.65rem] ${day.is_today ? 'font-bold text-yzh-gold' : 'text-yzh-slate'}`}
                            >
                                {new Date(day.date + 'T00:00:00').getDate()}
                            </span>
                            {holiday && (
                                <span className="block truncate border-l-2 border-yzh-gold bg-yzh-gold/10 px-1 font-mono text-[0.55rem] uppercase tracking-[0.1em] text-yzh-gold">
                                    {holiday}
                                </span>
                            )}
                            <div className="mt-0.5 space-y-0.5">
                                {dayEntries.map((e) => (
                                    <EntryBar key={`${e.id}-${day.date}`} entry={e} />
                                ))}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function Index({ view, year, month, week, days, entries, holidays, role }: Props) {
    const [deptId, setDeptId] = useState('');

    function navigate(params: Record<string, string | number>) {
        router.get('/leave-calendar', { view, year, month, week, ...params }, { replace: true, preserveState: false });
    }

    const title = view === 'week'
        ? `Week ${week ?? ''} · ${year}`
        : `${MONTH_NAMES[month - 1]} ${year}`;

    function prevPeriod() {
        if (view === 'month') {
            const d = new Date(year, month - 2, 1);
            navigate({ view: 'month', year: d.getFullYear(), month: d.getMonth() + 1 });
        } else {
            const w = (week ?? 1) - 1;
            navigate({ view: 'week', year: w < 1 ? year - 1 : year, week: w < 1 ? 52 : w });
        }
    }

    function nextPeriod() {
        if (view === 'month') {
            const d = new Date(year, month, 1);
            navigate({ view: 'month', year: d.getFullYear(), month: d.getMonth() + 1 });
        } else {
            const w = (week ?? 1) + 1;
            navigate({ view: 'week', year: w > 52 ? year + 1 : year, week: w > 52 ? 1 : w });
        }
    }

    return (
        <>
            <div className="space-y-6">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="flex items-baseline gap-3">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                    00
                                </span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                    {title}
                                </span>
                            </div>

                            <div className="flex items-center gap-2">
                                {/* View toggle */}
                                <button
                                    type="button"
                                    onClick={() => navigate({ view: 'month' })}
                                    className={`min-h-[44px] px-3 font-mono text-[0.6rem] uppercase tracking-[0.16em] border ${view === 'month' ? 'border-yzh-ink text-yzh-ink' : 'border-yzh-bone-soft text-yzh-slate hover:border-yzh-ink'}`}
                                >
                                    Month
                                </button>
                                <button
                                    type="button"
                                    onClick={() => navigate({ view: 'week' })}
                                    className={`min-h-[44px] px-3 font-mono text-[0.6rem] uppercase tracking-[0.16em] border ${view === 'week' ? 'border-yzh-ink text-yzh-ink' : 'border-yzh-bone-soft text-yzh-slate hover:border-yzh-ink'}`}
                                >
                                    Week
                                </button>

                                {/* Prev / Today / Next */}
                                <button
                                    type="button"
                                    onClick={prevPeriod}
                                    className="min-h-[44px] min-w-[44px] border border-yzh-bone-soft font-mono text-xs text-yzh-slate hover:border-yzh-ink hover:text-yzh-ink"
                                >
                                    ‹
                                </button>
                                <button
                                    type="button"
                                    onClick={() => navigate({ view, year: new Date().getFullYear(), month: new Date().getMonth() + 1 })}
                                    className="min-h-[44px] px-3 border border-yzh-bone-soft font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-slate hover:border-yzh-ink hover:text-yzh-ink"
                                >
                                    Today
                                </button>
                                <button
                                    type="button"
                                    onClick={nextPeriod}
                                    className="min-h-[44px] min-w-[44px] border border-yzh-bone-soft font-mono text-xs text-yzh-slate hover:border-yzh-ink hover:text-yzh-ink"
                                >
                                    ›
                                </button>
                            </div>
                        </div>

                        {/* HR filter bar */}
                        {role === 'hr' && (
                            <div className="mt-4 flex flex-wrap gap-3">
                                <input
                                    type="number"
                                    placeholder="Department ID"
                                    value={deptId}
                                    onChange={(e) => setDeptId(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') navigate({ department_id: deptId });
                                    }}
                                    className="min-h-[44px] w-40 border border-yzh-bone-soft px-3 font-mono text-xs text-yzh-ink focus:border-yzh-ink focus:outline-none"
                                />
                                <button
                                    type="button"
                                    onClick={() => navigate(deptId ? { department_id: deptId } : {})}
                                    className="min-h-[44px] border border-yzh-bone-soft px-4 font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-slate hover:border-yzh-ink hover:text-yzh-ink"
                                >
                                    Filter
                                </button>
                                {deptId && (
                                    <button
                                        type="button"
                                        onClick={() => { setDeptId(''); navigate({}); }}
                                        className="min-h-[44px] border border-yzh-bone-soft px-4 font-mono text-[0.6rem] uppercase tracking-[0.16em] text-yzh-slate hover:border-yzh-ink hover:text-yzh-ink"
                                    >
                                        Clear
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                </section>

                <section>
                    <DayGrid days={days} entries={entries} holidays={holidays} />
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
                    B.08 / Leave / Calendar
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Leave calendar.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Index;
