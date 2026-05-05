import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { Head, router, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Trash2 } from 'lucide-react';
import { ReactNode } from 'react';

type HolidayRow = { id: number; name: string; date: string; is_make_up: boolean };
type Props = { holidays: HolidayRow[]; year: number };

export default function HolidayCalendar({ holidays, year }: Props) {
    const create = useForm({ name: '', date: '', is_make_up: false });
    const del = useForm({});

    function submitCreate(e: React.FormEvent) {
        e.preventDefault();
        create.post('/admin/holidays', { onSuccess: () => create.reset() });
    }

    function destroy(h: HolidayRow) {
        if (!confirm(`Remove "${h.name}" from the calendar?`)) return;
        del.delete(`/admin/holidays/${h.id}`);
    }

    function goYear(delta: number) {
        router.get('/holidays', { year: year + delta }, { preserveState: true, replace: true });
    }

    const byMonth = holidays.reduce<Record<number, HolidayRow[]>>((acc, h) => {
        const m = new Date(h.date).getMonth();
        if (!acc[m]) acc[m] = [];
        acc[m].push(h);
        return acc;
    }, {});

    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    return (
        <>
            <Head title="Holiday Calendar" />
            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Add holiday">
                    <form onSubmit={submitCreate} className="mt-6 flex flex-wrap items-end gap-4">
                        <div>
                            <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Name</label>
                            <input value={create.data.name} onChange={e => create.setData('name', e.target.value)}
                                className="input-base w-64" placeholder="e.g. Eid Al-Adha" />
                            {create.errors.name && <p className="mt-1 text-xs text-red-600">{create.errors.name}</p>}
                        </div>
                        <div>
                            <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Date</label>
                            <input type="date" value={create.data.date} onChange={e => create.setData('date', e.target.value)}
                                className="input-base" />
                            {create.errors.date && <p className="mt-1 text-xs text-red-600">{create.errors.date}</p>}
                        </div>
                        <label className="flex items-center gap-2 text-sm text-yzh-ink">
                            <input type="checkbox" checked={create.data.is_make_up} onChange={e => create.setData('is_make_up', e.target.checked)}
                                className="h-4 w-4 rounded border-yzh-bone-soft text-yzh-gold focus:ring-yzh-gold" />
                            Make-up day
                        </label>
                        <button type="submit" disabled={create.processing}
                            className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                            Add
                        </button>
                    </form>
                </Section>

                <Section number="01" label={`${holidays.length} holidays`}>
                    <div className="mt-4 flex items-center gap-4">
                        <button onClick={() => goYear(-1)} className="inline-flex h-9 w-9 items-center justify-center border border-yzh-bone-soft text-yzh-text hover:border-yzh-ink hover:text-yzh-ink">
                            <ChevronLeft className="h-4 w-4" />
                        </button>
                        <span className="font-mono text-sm uppercase tracking-[0.22em] text-yzh-ink">{year}</span>
                        <button onClick={() => goYear(1)} className="inline-flex h-9 w-9 items-center justify-center border border-yzh-bone-soft text-yzh-text hover:border-yzh-ink hover:text-yzh-ink">
                            <ChevronRight className="h-4 w-4" />
                        </button>
                    </div>

                    {holidays.length === 0 ? (
                        <p className="mt-6 text-sm text-yzh-slate">No holidays for {year}.</p>
                    ) : (
                        <div className="mt-6 space-y-8">
                            {MONTHS.map((month, idx) => {
                                const rows = byMonth[idx] ?? [];
                                if (!rows.length) return null;
                                return (
                                    <div key={idx}>
                                        <p className="mb-3 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">{month}</p>
                                        <ul className="divide-y divide-yzh-bone-soft">
                                            {rows.sort((a, b) => a.date.localeCompare(b.date)).map(h => (
                                                <li key={h.id} className="flex items-center justify-between py-3">
                                                    <div className="flex items-center gap-4">
                                                        <span className="w-28 font-mono text-xs text-yzh-text">{formatDate(new Date(h.date))}</span>
                                                        <span className="text-base font-medium text-yzh-ink">{h.name}</span>
                                                        {h.is_make_up && (
                                                            <span className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-gold">Make-up</span>
                                                        )}
                                                    </div>
                                                    <button onClick={() => destroy(h)} disabled={del.processing}
                                                        className="inline-flex h-8 w-8 items-center justify-center text-yzh-text hover:text-red-600 disabled:opacity-40">
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </Section>
            </div>
        </>
    );
}

function Section({ number, label, children }: { number: string; label: string; children: ReactNode }) {
    return (
        <section>
            <div className="border-t border-yzh-bone-soft pt-5">
                <div className="flex items-baseline gap-3">
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">{number}</span>
                    <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">{label}</span>
                </div>
                {children}
            </div>
        </section>
    );
}

HolidayCalendar.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">S.04 / Settings / Holiday Calendar</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Holiday calendar.</h1>
        </div>
    }>{page}</AppLayout>
);
