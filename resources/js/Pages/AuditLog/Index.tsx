import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { ChangeEvent, ReactNode } from 'react';

type AuditEntry = {
    id: number;
    action: string;
    entity_type: string | null;
    entity_id: string | null;
    user: { name: string; email: string } | null;
    ip_address: string | null;
    changes: Record<string, { before: unknown; after: unknown }> | null;
    created_at: string | null;
};

type Paginated = {
    data: AuditEntry[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    entries: Paginated;
    filters: { action?: string; entity_type?: string; from?: string; to?: string };
    actions: string[];
};

const ACTION_COLORS: Record<string, string> = {
    created: 'text-yzh-gold',
    updated: 'text-yzh-ink',
    deleted: 'text-red-600',
    restored: 'text-yzh-text',
    'login.success': 'text-yzh-text',
    'login.failed': 'text-red-400',
};

export default function Index({ entries, filters, actions }: Props) {
    function setFilter(key: string, value: string) {
        router.get('/admin/audit-log', { ...filters, [key]: value || undefined }, {
            preserveState: true, replace: true,
        });
    }

    function onSelect(e: ChangeEvent<HTMLSelectElement>, key: string) {
        setFilter(key, e.target.value);
    }

    return (
        <>
            <Head title="Audit Log" />
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Filters
                            </span>
                        </div>
                        <div className="mt-6 flex flex-wrap gap-4">
                            <select value={filters.action ?? ''} onChange={e => onSelect(e, 'action')} className="input-base w-48">
                                <option value="">All actions</option>
                                {actions.map(a => <option key={a} value={a}>{a}</option>)}
                            </select>
                            <input type="date" value={filters.from ?? ''} onChange={e => setFilter('from', e.target.value)} className="input-base w-44" placeholder="From date" />
                            <input type="date" value={filters.to ?? ''} onChange={e => setFilter('to', e.target.value)} className="input-base w-44" placeholder="To date" />
                        </div>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">01</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                {entries.total} entries
                            </span>
                        </div>

                        {entries.data.length === 0 ? (
                            <p className="mt-6 text-sm text-yzh-slate">No audit entries match the current filters.</p>
                        ) : (
                            <div className="mt-6 overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-yzh-bone-soft">
                                            <th className="pb-3 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Time</th>
                                            <th className="pb-3 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">User</th>
                                            <th className="pb-3 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Action</th>
                                            <th className="pb-3 pr-6 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Entity</th>
                                            <th className="pb-3 text-left font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">IP</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-yzh-bone-soft">
                                        {entries.data.map(e => (
                                            <tr key={e.id} className="align-top">
                                                <td className="py-3 pr-6 font-mono text-xs text-yzh-text whitespace-nowrap">
                                                    {e.created_at ? new Date(e.created_at).toLocaleString('en-GB', { dateStyle: 'short', timeStyle: 'short' }) : '—'}
                                                </td>
                                                <td className="py-3 pr-6">
                                                    {e.user ? (
                                                        <span className="text-yzh-ink">{e.user.name}</span>
                                                    ) : (
                                                        <span className="text-yzh-text">System</span>
                                                    )}
                                                </td>
                                                <td className={`py-3 pr-6 font-mono text-xs uppercase tracking-[0.18em] ${ACTION_COLORS[e.action] ?? 'text-yzh-text'}`}>
                                                    {e.action}
                                                </td>
                                                <td className="py-3 pr-6 font-mono text-xs text-yzh-text">
                                                    {e.entity_type && <span>{e.entity_type}</span>}
                                                    {e.entity_id && <span className="ml-1 text-yzh-gold">#{e.entity_id}</span>}
                                                </td>
                                                <td className="py-3 font-mono text-xs text-yzh-text">{e.ip_address ?? '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {entries.last_page > 1 && (
                            <div className="mt-8 flex items-center gap-2">
                                {entries.links.map((link, i) => (
                                    link.url ? (
                                        <button key={i} onClick={() => router.get(link.url!)}
                                            className={`inline-flex h-9 min-w-9 items-center justify-center border px-2 font-mono text-xs transition-colors ${link.active ? 'border-yzh-gold text-yzh-gold' : 'border-yzh-bone-soft text-yzh-text hover:border-yzh-ink hover:text-yzh-ink'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }} />
                                    ) : (
                                        <span key={i} className="inline-flex h-9 min-w-9 items-center justify-center border border-yzh-bone-soft px-2 font-mono text-xs text-yzh-bone-soft"
                                            dangerouslySetInnerHTML={{ __html: link.label }} />
                                    )
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">S.08 / Admin / Audit Log</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Audit log.</h1>
        </div>
    }>{page}</AppLayout>
);
