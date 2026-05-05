import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PenSquare, Pin } from 'lucide-react';
import { ReactNode, useState } from 'react';

type Department = { id: number; name: string };

type AnnouncementRow = {
    id: number;
    title: string;
    body: string;
    target_type: 'all' | 'department';
    target_id: number | null;
    pinned: boolean;
    published_at: string | null;
    expires_at: string | null;
    author: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    created_at: string | null;
};

type Paginated = {
    data: AnnouncementRow[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Props = {
    announcements: Paginated;
    can_create: boolean;
    departments: Department[];
};

export default function Index({ announcements, can_create, departments }: Props) {
    const [composing, setComposing] = useState(false);
    const form = useForm({
        title: '',
        body: '',
        target_type: 'all' as 'all' | 'department',
        target_id: '',
        pinned: false,
        published_at: '',
        expires_at: '',
    });

    function submitCreate(e: React.FormEvent) {
        e.preventDefault();
        form.post('/announcements', {
            onSuccess: () => { setComposing(false); form.reset(); },
        });
    }

    return (
        <>
            <Head title="Announcements" />
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex flex-wrap items-baseline justify-between gap-3">
                            <div className="flex items-baseline gap-3">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                    {announcements.total} announcement{announcements.total !== 1 ? 's' : ''}
                                </span>
                            </div>
                            {can_create && (
                                <button onClick={() => setComposing(v => !v)}
                                    className="inline-flex min-h-11 items-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink">
                                    <PenSquare className="h-3.5 w-3.5" /> New announcement
                                </button>
                            )}
                        </div>
                    </div>
                </section>

                {composing && can_create && (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5">
                            <div className="flex items-baseline gap-3 mb-6">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">01</span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">New announcement</span>
                            </div>
                            <form onSubmit={submitCreate} className="max-w-lg space-y-4">
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Title</label>
                                    <input value={form.data.title} onChange={e => form.setData('title', e.target.value)} className="input-base" />
                                    {form.errors.title && <p className="mt-1 text-xs text-red-600">{form.errors.title}</p>}
                                </div>
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Body</label>
                                    <textarea value={form.data.body} onChange={e => form.setData('body', e.target.value)}
                                        rows={5} className="input-base resize-none" />
                                    {form.errors.body && <p className="mt-1 text-xs text-red-600">{form.errors.body}</p>}
                                </div>
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Audience</label>
                                    <select value={form.data.target_type}
                                        onChange={e => form.setData('target_type', e.target.value as 'all' | 'department')}
                                        className="input-base">
                                        <option value="all">All employees</option>
                                        <option value="department">Specific department</option>
                                    </select>
                                </div>
                                {form.data.target_type === 'department' && (
                                    <div>
                                        <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Department</label>
                                        <select value={form.data.target_id}
                                            onChange={e => form.setData('target_id', e.target.value)}
                                            className="input-base">
                                            <option value="">— Select department —</option>
                                            {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                                        </select>
                                        {form.errors.target_id && <p className="mt-1 text-xs text-red-600">{form.errors.target_id}</p>}
                                    </div>
                                )}
                                <div className="flex gap-6">
                                    <div className="flex-1">
                                        <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Publish date (optional)</label>
                                        <input type="datetime-local" value={form.data.published_at}
                                            onChange={e => form.setData('published_at', e.target.value)}
                                            className="input-base" />
                                    </div>
                                    <div className="flex-1">
                                        <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Expires (optional)</label>
                                        <input type="datetime-local" value={form.data.expires_at}
                                            onChange={e => form.setData('expires_at', e.target.value)}
                                            className="input-base" />
                                    </div>
                                </div>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked={form.data.pinned}
                                        onChange={e => form.setData('pinned', e.target.checked)}
                                        className="h-4 w-4 rounded border-yzh-bone-soft accent-yzh-gold" />
                                    <span className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Pin to top</span>
                                </label>
                                <div className="flex gap-3">
                                    <button type="submit" disabled={form.processing}
                                        className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                                        Save as draft
                                    </button>
                                    <button type="button" disabled={form.processing}
                                        onClick={() => { form.setData('published_at', new Date().toISOString().slice(0, 16)); setTimeout(() => form.post('/announcements', { onSuccess: () => { setComposing(false); form.reset(); } }), 50); }}
                                        className="inline-flex min-h-11 items-center bg-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-ink hover:bg-yzh-gold/90 disabled:opacity-50">
                                        Publish now
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                )}

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        {announcements.data.length === 0 ? (
                            <p className="text-sm text-yzh-slate">No announcements yet.</p>
                        ) : (
                            <ul className="divide-y divide-yzh-bone-soft">
                                {announcements.data.map(a => (
                                    <li key={a.id}>
                                        <Link href={`/announcements/${a.id}`}
                                            className="flex items-start justify-between gap-4 py-4 hover:bg-yzh-bone-soft/30">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2">
                                                    {a.pinned && <Pin className="h-3 w-3 shrink-0 text-yzh-gold" />}
                                                    {!a.published_at && (
                                                        <span className="font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-slate border border-yzh-bone-soft px-1">Draft</span>
                                                    )}
                                                    <span className="font-semibold text-yzh-ink">{a.title}</span>
                                                </div>
                                                <p className="mt-0.5 truncate text-sm text-yzh-slate">
                                                    {a.target_type === 'all' ? 'All employees' : `${a.department?.name ?? 'Dept.'}`}
                                                    {' — '}{a.body.slice(0, 80)}
                                                </p>
                                            </div>
                                            <span className="shrink-0 font-mono text-[0.6875rem] text-yzh-text">
                                                {a.published_at
                                                    ? new Date(a.published_at).toLocaleDateString('en-GB')
                                                    : a.created_at ? new Date(a.created_at).toLocaleDateString('en-GB') : ''}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>

                {announcements.last_page > 1 && (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5 flex items-center justify-between">
                            <span className="font-mono text-[0.6875rem] text-yzh-text">
                                {announcements.from}–{announcements.to} of {announcements.total}
                            </span>
                            <div className="flex gap-3">
                                {announcements.current_page > 1 && (
                                    <Link href={`/announcements?page=${announcements.current_page - 1}`}
                                        className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-gold hover:text-yzh-ink">
                                        ← Prev
                                    </Link>
                                )}
                                {announcements.current_page < announcements.last_page && (
                                    <Link href={`/announcements?page=${announcements.current_page + 1}`}
                                        className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-gold hover:text-yzh-ink">
                                        Next →
                                    </Link>
                                )}
                            </div>
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.02 / Announcements</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Announcements.</h1>
        </div>
    }>{page}</AppLayout>
);
