import AppLayout from '@/Layouts/AppLayout';
import EmptyState from '@/Components/EmptyState';
import { Head, Link, useForm } from '@inertiajs/react';
import { Inbox, PenSquare, Send } from 'lucide-react';
import { ReactNode, useState } from 'react';

type MessageRow = {
    id: number;
    subject: string;
    body: string;
    sender: { id: number; name: string } | null;
    recipient: { id: number; name: string } | null;
    read_at: string | null;
    created_at: string | null;
};

type Contact = { id: number; name: string; email: string };

type Paginated = {
    data: MessageRow[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Props = {
    messages: Paginated;
    tab: string;
    contacts: Contact[];
    unread_count: number;
};

export default function Index({ messages, tab, contacts, unread_count }: Props) {
    const [composing, setComposing] = useState(false);
    const form = useForm({ recipient_id: '', subject: '', body: '', parent_message_id: '' });

    function submitCompose(e: React.FormEvent) {
        e.preventDefault();
        form.post('/messages', { onSuccess: () => { setComposing(false); form.reset(); } });
    }

    return (
        <>
            <Head title="Messages" />
            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex flex-wrap items-baseline justify-between gap-3">
                            <div className="flex items-baseline gap-4">
                                <TabLink href="/messages" active={tab === 'inbox'} label={`Inbox ${unread_count > 0 ? `(${unread_count})` : ''}`} />
                                <TabLink href="/messages?tab=sent" active={tab === 'sent'} label="Sent" />
                            </div>
                            <button onClick={() => setComposing(v => !v)}
                                className="inline-flex min-h-11 items-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink">
                                <PenSquare className="h-3.5 w-3.5" /> Compose
                            </button>
                        </div>
                    </div>
                </section>

                {composing && (
                    <section>
                        <div className="border-t border-yzh-bone-soft pt-5">
                            <div className="flex items-baseline gap-3 mb-6">
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">00</span>
                                <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">New message</span>
                            </div>
                            <form onSubmit={submitCompose} className="max-w-lg space-y-4">
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">To</label>
                                    <select value={form.data.recipient_id} onChange={e => form.setData('recipient_id', e.target.value)} className="input-base">
                                        <option value="">— Select recipient —</option>
                                        {contacts.map(c => <option key={c.id} value={c.id}>{c.name} ({c.email})</option>)}
                                    </select>
                                    {form.errors.recipient_id && <p className="mt-1 text-xs text-red-600">{form.errors.recipient_id}</p>}
                                </div>
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Subject</label>
                                    <input value={form.data.subject} onChange={e => form.setData('subject', e.target.value)} className="input-base" />
                                    {form.errors.subject && <p className="mt-1 text-xs text-red-600">{form.errors.subject}</p>}
                                </div>
                                <div>
                                    <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">Message</label>
                                    <textarea value={form.data.body} onChange={e => form.setData('body', e.target.value)}
                                        rows={5} className="input-base resize-none" />
                                    {form.errors.body && <p className="mt-1 text-xs text-red-600">{form.errors.body}</p>}
                                </div>
                                <button type="submit" disabled={form.processing}
                                    className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                                    Send
                                </button>
                            </form>
                        </div>
                    </section>
                )}

                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3 mb-6">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">{composing ? '01' : '00'}</span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">{messages.total} {tab === 'sent' ? 'sent' : 'received'}</span>
                        </div>
                        {messages.data.length === 0 ? (
                            tab === 'inbox' ? (
                                <EmptyState
                                    icon={Inbox}
                                    heading="Your inbox is empty"
                                    description="Messages sent to you will appear here. Use Compose to start a thread with a teammate."
                                />
                            ) : (
                                <EmptyState
                                    icon={Send}
                                    heading="No sent messages"
                                    description="Threads you start will be listed here for follow-up."
                                />
                            )
                        ) : (
                            <ul className="divide-y divide-yzh-bone-soft">
                                {messages.data.map(m => (
                                    <li key={m.id}>
                                        <Link href={`/messages/${m.id}`} className="flex items-start justify-between gap-4 py-4 hover:bg-yzh-bone-soft/30">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-3">
                                                    {!m.read_at && tab === 'inbox' && (
                                                        <span className="h-2 w-2 shrink-0 rounded-full bg-yzh-gold" aria-label="Unread" />
                                                    )}
                                                    <span className={`text-base ${!m.read_at && tab === 'inbox' ? 'font-semibold text-yzh-ink' : 'text-yzh-ink'}`}>
                                                        {m.subject}
                                                    </span>
                                                </div>
                                                <p className="mt-0.5 truncate text-sm text-yzh-slate">
                                                    {tab === 'inbox' ? `From ${m.sender?.name ?? 'System'}` : `To ${m.recipient?.name}`}
                                                    {' — '}{m.body.slice(0, 80)}
                                                </p>
                                            </div>
                                            <span className="shrink-0 font-mono text-[0.6875rem] text-yzh-text">
                                                {m.created_at ? new Date(m.created_at).toLocaleDateString('en-GB') : ''}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

function TabLink({ href, active, label }: { href: string; active: boolean; label: string }) {
    return (
        <Link href={href} className={`font-mono text-xs uppercase tracking-[0.22em] transition-colors ${active ? 'text-yzh-gold' : 'text-yzh-text hover:text-yzh-ink'}`}>
            {label}
        </Link>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">G.01 / Messages</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Messages.</h1>
        </div>
    }>{page}</AppLayout>
);
