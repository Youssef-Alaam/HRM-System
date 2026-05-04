import ExportButtons from '@/Components/ExportButtons';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ChangeEvent, ReactNode, useEffect, useState } from 'react';

type EmployeeRow = {
    id: number;
    employee_code: string;
    first_name: string;
    last_name: string;
    email: string;
    employment_status: string;
    hiring_date: string | null;
    department: { id: number; name: string } | null;
    position: { id: number; title: string } | null;
    office: { id: number; name: string } | null;
};

type Paginated = {
    data: EmployeeRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Filters = {
    search?: string;
    department_id?: number;
    employment_status?: string;
};

type Props = {
    employees: Paginated;
    filters: Filters;
    canCreate: boolean;
};

function Index({ employees, filters, canCreate }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    // Scoped loading state for same-page refreshes (search/filter/pagination).
    // AppLayout's full-page Compass Arc is suppressed for same-pathname visits,
    // so the page renders its own pulsing-dot indicator inline.
    const [searching, setSearching] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', (event) => {
            try {
                const url = new URL(
                    event.detail.visit.url.toString(),
                    window.location.origin,
                );
                if (url.pathname === '/employees') setSearching(true);
            } catch {
                /* invalid URL — ignore */
            }
        });
        const offFinish = router.on('finish', () => setSearching(false));
        return () => {
            offStart();
            offFinish();
        };
    }, []);

    const onSearchChange = (e: ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setSearch(value);
        router.get(
            '/employees',
            { ...filters, search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Employees" />

            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex flex-wrap items-baseline justify-between gap-3">
                            <div className="flex items-baseline gap-3">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                    00
                                </span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                    Roster / {employees.total} active
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <ExportButtons resource="employees" />
                                {canCreate && (
                                    <Link
                                        href="/employees/create"
                                        className="group inline-flex min-h-11 items-center gap-3 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                                    >
                                        New employee
                                    </Link>
                                )}
                            </div>
                        </div>

                        <div className="mt-6">
                            <input
                                type="search"
                                value={search}
                                onChange={onSearchChange}
                                placeholder="Search by name, email, code"
                                className="block h-11 w-full max-w-md rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink placeholder:text-yzh-text shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                            />
                        </div>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                01
                            </span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Drawing index
                            </span>
                            {searching && (
                                <span
                                    className="ml-1 inline-flex items-center gap-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold"
                                    role="status"
                                    aria-live="polite"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="h-1.5 w-1.5 rounded-full bg-yzh-gold motion-safe:animate-pulse"
                                    />
                                    Searching
                                </span>
                            )}
                        </div>

                        {employees.data.length === 0 ? (
                            <div className="mt-8 text-sm text-yzh-slate">
                                No employees match your filters.
                            </div>
                        ) : (
                            <ul className="mt-6 divide-y divide-yzh-bone-soft">
                                {employees.data.map((emp) => (
                                    <Row key={emp.id} employee={emp} />
                                ))}
                            </ul>
                        )}

                        {employees.last_page > 1 && (
                            <div className="mt-8 flex items-center justify-between font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                                <span>
                                    Showing {employees.from ?? 0} – {employees.to ?? 0} of {employees.total}
                                </span>
                                <span>
                                    Page {employees.current_page} of {employees.last_page}
                                </span>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

function Row({ employee }: { employee: EmployeeRow }) {
    return (
        <li>
            <Link
                href={`/employees/${employee.id}`}
                className="grid grid-cols-12 gap-4 py-5 transition-colors duration-150 ease-out hover:bg-yzh-bone-soft/30 sm:gap-6"
            >
                <span className="col-span-3 font-mono text-xs uppercase tracking-[0.18em] text-yzh-text sm:col-span-2">
                    {employee.employee_code}
                </span>
                <span className="col-span-9 text-base font-semibold text-yzh-ink sm:col-span-3">
                    {employee.first_name} {employee.last_name}
                </span>
                <span className="col-span-12 truncate font-mono text-xs text-yzh-slate sm:col-span-3">
                    {employee.email}
                </span>
                <span className="col-span-6 text-sm text-yzh-slate sm:col-span-2">
                    {employee.department?.name ?? '—'}
                </span>
                <span className="col-span-6 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold sm:col-span-2">
                    {employee.employment_status}
                </span>
            </Link>
        </li>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    B.01 / People
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Employees.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Index;
