import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { ChangeEvent, ReactNode, useMemo, useState } from 'react';

type ComplianceRow = {
    id: number;
    employee_code: string;
    first_name: string;
    last_name: string;
    is_expat: boolean;
    employment_status: string;
    required_count: number;
    uploaded_count: number;
    missing_count: number;
    missing: { id: number; name: string }[];
    expiring_soon_count: number;
    expired_count: number;
};

type Props = {
    employees: ComplianceRow[];
    totals: {
        employees: number;
        missing_any: number;
        expiring_30d: number;
        expired: number;
    };
};

function Index({ employees, totals }: Props) {
    const [search, setSearch] = useState('');

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();
        const sorted = [...employees].sort((a, b) => {
            if (a.missing_count !== b.missing_count) {
                return b.missing_count - a.missing_count;
            }
            return a.first_name.localeCompare(b.first_name);
        });
        if (term === '') return sorted;
        return sorted.filter((row) => {
            const name = `${row.first_name} ${row.last_name}`.toLowerCase();
            return (
                name.includes(term) ||
                row.employee_code.toLowerCase().includes(term)
            );
        });
    }, [employees, search]);

    const onSearchChange = (e: ChangeEvent<HTMLInputElement>) => {
        setSearch(e.target.value);
    };

    return (
        <>
            <Head title="Documents" />

            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label={`Compliance / ${totals.employees} active`}>
                    <dl className="grid grid-cols-2 gap-x-8 gap-y-6 sm:grid-cols-4">
                        <Stat
                            label="Missing required"
                            value={totals.missing_any}
                            tone={totals.missing_any > 0 ? 'warn' : 'ok'}
                        />
                        <Stat
                            label="Expiring < 30 days"
                            value={totals.expiring_30d}
                            tone={totals.expiring_30d > 0 ? 'warn' : 'ok'}
                        />
                        <Stat
                            label="Expired"
                            value={totals.expired}
                            tone={totals.expired > 0 ? 'danger' : 'ok'}
                        />
                        <Stat
                            label="Total employees"
                            value={totals.employees}
                            tone="ok"
                        />
                    </dl>
                </Section>

                <Section number="01" label="Roster compliance">
                    <div className="mb-6">
                        <input
                            type="search"
                            value={search}
                            onChange={onSearchChange}
                            placeholder="Search by name or employee code"
                            className="block h-11 w-full max-w-md rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink placeholder:text-yzh-text shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                        />
                    </div>
                    {filtered.length === 0 ? (
                        <div className="mt-8 text-sm text-yzh-slate">
                            {employees.length === 0
                                ? 'No active employees in the roster yet.'
                                : 'No employees match your search.'}
                        </div>
                    ) : (
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {filtered.map((row) => (
                                <Row key={row.id} row={row} />
                            ))}
                        </ul>
                    )}
                </Section>
            </div>
        </>
    );
}

function Row({ row }: { row: ComplianceRow }) {
    const fullName = `${row.first_name} ${row.last_name}`;
    const status =
        row.missing_count > 0
            ? { tone: 'warn' as const, label: `${row.missing_count} missing` }
            : row.expired_count > 0
              ? { tone: 'danger' as const, label: `${row.expired_count} expired` }
              : row.expiring_soon_count > 0
                ? {
                      tone: 'soft' as const,
                      label: `${row.expiring_soon_count} expiring`,
                  }
                : { tone: 'ok' as const, label: 'Complete' };

    const toneClass =
        status.tone === 'danger'
            ? 'text-red-700'
            : status.tone === 'warn'
              ? 'text-yzh-gold'
              : status.tone === 'soft'
                ? 'text-yzh-slate'
                : 'text-yzh-text';

    return (
        <li>
            <Link
                href={`/employees/${row.id}`}
                className="grid grid-cols-12 gap-4 py-5 transition-colors duration-150 ease-out hover:bg-yzh-bone-soft/30 sm:gap-6"
            >
                <span className="col-span-3 font-mono text-xs uppercase tracking-[0.18em] text-yzh-text sm:col-span-2">
                    {row.employee_code}
                </span>
                <span className="col-span-9 text-base font-semibold text-yzh-ink sm:col-span-3">
                    {fullName}
                </span>
                <span className="col-span-6 text-sm text-yzh-slate sm:col-span-3">
                    {row.uploaded_count} / {row.required_count} required uploaded
                </span>
                <span
                    className={`col-span-6 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] sm:col-span-4 ${toneClass}`}
                >
                    {status.label}
                </span>
            </Link>
        </li>
    );
}

function Stat({
    label,
    value,
    tone,
}: {
    label: string;
    value: number;
    tone: 'ok' | 'warn' | 'danger';
}) {
    const toneClass =
        tone === 'danger'
            ? 'text-red-700'
            : tone === 'warn'
              ? 'text-yzh-gold'
              : 'text-yzh-ink';

    return (
        <div>
            <dt className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                {label}
            </dt>
            <dd
                className={`mt-2 text-3xl font-semibold tracking-tight ${toneClass}`}
            >
                {value}
            </dd>
        </div>
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
                <div className="mt-6">{children}</div>
            </div>
        </section>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    B.03 / People / Documents
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Documents.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Index;
