import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ReactNode } from 'react';

type MessageRow = {
    id: number;
    subject: string;
    body: string;
    sender: { id: number; name: string } | null;
    recipient: { id: number; name: string } | null;
    read_at: string | null;
    created_at: string | null;
};

type Props = { thread: MessageRow[]; root_id: number };

export default function Show({ thread, root_id }: Props) {
    const root = thread[0];
    const form = useForm({ recipient_id: root?.sender?.id?.toString() ?? '', subject: `Re: ${root?.subject ?? ''}`, body: '', parent_message_id: root_id.toString() });

    function submitReply(e: React.FormEvent) {
        e.preventDefault();
        form.post('/messages', { onSuccess: () => form.setData('body', '') });
    }

    return (
        <>
            <Head title={root?.subject ?? 'Message'} />
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-2">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Thread</span>
                        </div>
                        <Link href="/messages" className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text hover:text-yzh-ink">
                            ← Back to inbox
                        </Link>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <h2 className="mb-6 text-xl font-semibold text-yzh-ink">{root?.subject}</h2>
                        <div className="space-y-6">
                            {thread.map((m, i) => (
                                <div key={m.id} className={`rounded-sm border-l-2 pl-4 ${i === 0 ? 'border-yzh-gold' : 'border-yzh-bone-soft'}`}>
                                    <div className="mb-1 flex items-baseline gap-3">
                                        <span className="font-semibold text-yzh-ink">{m.sender?.name ?? 'System'}</span>
                                        <span className="font-mono text-[0.6875rem] text-yzh-text">→ {m.recipient?.name}</span>
                                        <span className="ml-auto font-mono text-[0.6875rem] text-yzh-text">
                                            {m.created_at ? new Date(m.created_at).toLocaleString('en-GB', { dateStyle: 'short', timeStyle: 'short' }) : ''}
                                        </span>
                                    </div>
                                    <p className="whitespace-pre-wrap text-sm text-yzh-ink">{m.body}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">01</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">Reply</span>
                        </div>
                        <form onSubmit={submitReply} className="max-w-lg space-y-4">
                            <div>
                                <textarea value={form.data.body} onChange={e => form.setData('body', e.target.value)}
                                    rows={4} className="input-base resize-none" placeholder="Write your reply…" />
                                {form.errors.body && <p className="mt-1 text-xs text-red-600">{form.errors.body}</p>}
                            </div>
                            <button type="submit" disabled={form.processing}
                                className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                                Send reply
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </>
    );
}

Show.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.01 / Messages / Thread</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Message.</h1>
        </div>
    }>{page}</AppLayout>
);
