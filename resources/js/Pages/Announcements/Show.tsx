import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Pin, Trash2 } from 'lucide-react';
import { ReactNode, useState } from 'react';

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

type Props = { announcement: AnnouncementRow; can_edit: boolean };

export default function Show({ announcement: a, can_edit }: Props) {
    const [editing, setEditing] = useState(false);
    const form = useForm({
        title: a.title,
        body: a.body,
        target_type: a.target_type,
        target_id: a.target_id?.toString() ?? '',
        pinned: a.pinned,
        expires_at: a.expires_at ? new Date(a.expires_at).toISOString().slice(0, 16) : '',
    });

    function submitUpdate(e: React.FormEvent) {
        e.preventDefault();
        form.patch(`/announcements/${a.id}`, { onSuccess: () => setEditing(false) });
    }

    function handlePublish() {
        router.post(`/announcements/${a.id}/publish`);
    }

    function handleDelete() {
        if (confirm('Delete this announcement?')) {
            router.delete(`/announcements/${a.id}`);
        }
    }

    return (
        <>
            <Head title={a.title} />
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <Link href="/announcements"
                            className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text hover:text-yzh-ink">
                            ← Back to announcements
                        </Link>
                    </div>
                </section>

                {!editing ? (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5">
                            <div className="flex items-start justify-between gap-4 mb-6">
                                <div>
                                    <div className="flex items-center gap-2 mb-2">
                                        {a.pinned && <Pin className="h-4 w-4 text-yzh-gold" />}
                                        {!a.published_at && (
                                            <span className="font-mono text-[0.6rem] uppercase tracking-[0.18em] text-yzh-slate border border-yzh-bone-soft px-1">Draft</span>
                                        )}
                                    </div>
                                    <h2 className="text-xl font-semibold text-yzh-ink">{a.title}</h2>
                                    <div className="mt-1 flex flex-wrap gap-4">
                                        <span className="font-mono text-[0.6875rem] text-yzh-text">
                                            By {a.author?.name ?? 'System'}
                                        </span>
                                        {a.published_at && (
                                            <span className="font-mono text-[0.6875rem] text-yzh-text">
                                                Published {new Date(a.published_at).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })}
                                            </span>
                                        )}
                                        <span className="font-mono text-[0.6875rem] text-yzh-text">
                                            {a.target_type === 'all' ? 'Audience: All employees' : `Audience: ${a.department?.name ?? 'Department'}`}
                                        </span>
                                        {a.expires_at && (
                                            <span className="font-mono text-[0.6875rem] text-yzh-text">
                                                Expires {new Date(a.expires_at).toLocaleDateString('en-GB')}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                {can_edit && (
                                    <div className="flex shrink-0 gap-2">
                                        {!a.published_at && (
                                            <button onClick={handlePublish}
                                                className="inline-flex min-h-11 items-center bg-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-ink hover:bg-yzh-gold/90">
                                                Publish
                                            </button>
                                        )}
                                        <button onClick={() => setEditing(true)}
                                            className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink">
                                            Edit
                                        </button>
                                        <button onClick={handleDelete}
                                            className="inline-flex min-h-11 items-center border border-red-200 px-3 py-2 text-red-500 hover:bg-red-50">
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                )}
                            </div>
                            <div className="border-t border-yzh-bone-soft pt-6">
                                <p className="whitespace-pre-wrap text-sm text-yzh-ink leading-relaxed">{a.body}</p>
                            </div>
                        </div>
                    </section>
                ) : (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5">
                            <div className="flex items-baseline gap-3 mb-6">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">01</span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Edit announcement</span>
                            </div>
                            <form onSubmit={submitUpdate} className="max-w-lg space-y-4">
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Title</label>
                                    <input value={form.data.title} onChange={e => form.setData('title', e.target.value)} className="input-base" />
                                    {form.errors.title && <p className="mt-1 text-xs text-red-600">{form.errors.title}</p>}
                                </div>
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Body</label>
                                    <textarea value={form.data.body} onChange={e => form.setData('body', e.target.value)}
                                        rows={6} className="input-base resize-none" />
                                    {form.errors.body && <p className="mt-1 text-xs text-red-600">{form.errors.body}</p>}
                                </div>
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Expires (optional)</label>
                                    <input type="datetime-local" value={form.data.expires_at}
                                        onChange={e => form.setData('expires_at', e.target.value)}
                                        className="input-base" />
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
                                        Save
                                    </button>
                                    <button type="button" onClick={() => setEditing(false)}
                                        className="inline-flex min-h-11 items-center border border-yzh-bone-soft px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-text hover:text-yzh-ink">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

Show.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.02 / Announcements / Detail</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Announcement.</h1>
        </div>
    }>{page}</AppLayout>
);
