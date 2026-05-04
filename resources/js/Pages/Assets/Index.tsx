import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatMoneyEgp } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { ChangeEvent, ReactNode, useEffect, useState } from 'react';

type CategoryRef = { id: number; name: string };

type AssetRow = {
    id: number;
    name: string;
    serial_number: string | null;
    model: string | null;
    value_piasters: number;
    acquired_date: string | null;
    condition_at_acquisition: string;
    current_status:
        | 'in_pool'
        | 'assigned'
        | 'lost'
        | 'damaged'
        | 'written_off';
    category: CategoryRef | null;
    current_employee: {
        id: number;
        employee_code: string;
        name: string;
    } | null;
};

type Paginated = {
    data: AssetRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type OrgFilters = {
    asset_category_id?: number;
    current_status?: string;
    search?: string;
};

type OrgProps = {
    mode: 'org';
    assets: Paginated;
    categories: { id: number; name: string; icon_name: string }[];
    filters: OrgFilters;
    canCreate: boolean;
    canAssign: boolean;
    statuses: string[];
};

type SelfProps = {
    mode: 'self';
    own: AssetRow[];
};

type Props = OrgProps | SelfProps;

function Index(props: Props) {
    if (props.mode === 'self') {
        return <SelfView own={props.own} />;
    }
    return <OrgView {...props} />;
}

function SelfView({ own }: { own: AssetRow[] }) {
    return (
        <>
            <Head title="My assets" />
            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label={`Your assets · ${own.length}`}>
                    {own.length === 0 ? (
                        <p className="mt-4 text-sm text-yzh-slate">
                            You currently have no assets assigned. HR will
                            update this when equipment is issued.
                        </p>
                    ) : (
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {own.map((asset) => (
                                <li
                                    key={asset.id}
                                    className="grid grid-cols-12 gap-4 py-5 sm:gap-6"
                                >
                                    <span className="col-span-3 font-mono text-xs uppercase tracking-[0.18em] text-yzh-text sm:col-span-2">
                                        {asset.category?.name ?? '—'}
                                    </span>
                                    <span className="col-span-9 text-base font-semibold text-yzh-ink sm:col-span-4">
                                        {asset.name}
                                    </span>
                                    <span className="col-span-6 truncate font-mono text-xs text-yzh-slate sm:col-span-3">
                                        {asset.serial_number ?? '—'}
                                    </span>
                                    <span className="col-span-6 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text sm:col-span-3">
                                        {formatMoneyEgp(asset.value_piasters)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>
            </div>
        </>
    );
}

function OrgView({ assets, categories, filters, canCreate, statuses }: OrgProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [searching, setSearching] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', (event) => {
            try {
                const url = new URL(
                    event.detail.visit.url.toString(),
                    window.location.origin,
                );
                if (url.pathname === '/assets') setSearching(true);
            } catch {
                /* ignore */
            }
        });
        const offFinish = router.on('finish', () => setSearching(false));
        return () => {
            offStart();
            offFinish();
        };
    }, []);

    const refine = (next: Partial<OrgFilters>) => {
        router.get(
            '/assets',
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const onSearchChange = (e: ChangeEvent<HTMLInputElement>) => {
        setSearch(e.target.value);
        refine({ search: e.target.value || undefined });
    };

    return (
        <>
            <Head title="Assets" />
            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label={`Inventory · ${assets.total} assets`}>
                    <div className="flex flex-wrap items-baseline justify-between gap-3">
                        <div className="flex flex-wrap items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Org-wide roster
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
                        {canCreate && (
                            <Link
                                href="/assets/create"
                                className="group inline-flex min-h-11 items-center gap-3 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                            >
                                New asset
                            </Link>
                        )}
                    </div>

                    <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <input
                            type="search"
                            value={search}
                            onChange={onSearchChange}
                            placeholder="Search by name, serial, model"
                            className="block h-11 rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink placeholder:text-yzh-text shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                        />
                        <Select
                            value={filters.asset_category_id ?? ''}
                            onChange={(e) =>
                                refine({
                                    asset_category_id:
                                        e.target.value === ''
                                            ? undefined
                                            : Number(e.target.value),
                                })
                            }
                        >
                            <option value="">All categories</option>
                            {categories.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={filters.current_status ?? ''}
                            onChange={(e) =>
                                refine({
                                    current_status:
                                        e.target.value === ''
                                            ? undefined
                                            : e.target.value,
                                })
                            }
                        >
                            <option value="">All statuses</option>
                            {statuses.map((s) => (
                                <option key={s} value={s}>
                                    {s.replace('_', ' ')}
                                </option>
                            ))}
                        </Select>
                    </div>
                </Section>

                <Section number="01" label="Drawing index">
                    {assets.data.length === 0 ? (
                        <div className="mt-8 text-sm text-yzh-slate">
                            No assets match your filters.
                        </div>
                    ) : (
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {assets.data.map((asset) => (
                                <Row key={asset.id} asset={asset} />
                            ))}
                        </ul>
                    )}

                    {assets.last_page > 1 && (
                        <div className="mt-8 flex items-center justify-between font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                            <span>
                                Showing {assets.from ?? 0} – {assets.to ?? 0} of {assets.total}
                            </span>
                            <span>
                                Page {assets.current_page} of {assets.last_page}
                            </span>
                        </div>
                    )}
                </Section>
            </div>
        </>
    );
}

function Row({ asset }: { asset: AssetRow }) {
    const statusTone =
        asset.current_status === 'lost' || asset.current_status === 'damaged'
            ? 'text-red-700'
            : asset.current_status === 'assigned'
              ? 'text-yzh-gold'
              : 'text-yzh-text';

    return (
        <li>
            <Link
                href={`/assets/${asset.id}`}
                className="grid grid-cols-12 gap-4 py-5 transition-colors duration-150 ease-out hover:bg-yzh-bone-soft/30 sm:gap-6"
            >
                <span className="col-span-3 font-mono text-xs uppercase tracking-[0.18em] text-yzh-text sm:col-span-2">
                    {asset.category?.name ?? '—'}
                </span>
                <span className="col-span-9 text-base font-semibold text-yzh-ink sm:col-span-3">
                    {asset.name}
                </span>
                <span className="col-span-6 truncate font-mono text-xs text-yzh-slate sm:col-span-2">
                    {asset.serial_number ?? '—'}
                </span>
                <span className="col-span-6 text-sm text-yzh-slate sm:col-span-2">
                    {asset.current_employee
                        ? asset.current_employee.name
                        : (asset.acquired_date ? formatDate(asset.acquired_date) : '—')}
                </span>
                <span
                    className={`col-span-12 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] sm:col-span-3 ${statusTone}`}
                >
                    {asset.current_status.replace('_', ' ')} ·{' '}
                    {formatMoneyEgp(asset.value_piasters)}
                </span>
            </Link>
        </li>
    );
}

function Select({
    children,
    className = '',
    ...props
}: React.SelectHTMLAttributes<HTMLSelectElement>) {
    return (
        <select
            {...props}
            className={
                'block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30 ' +
                className
            }
        >
            {children}
        </select>
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
                    B.04 / People / Assets
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Assets.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Index;
