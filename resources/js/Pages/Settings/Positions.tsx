import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Trash2, X } from 'lucide-react';
import { ReactNode, useState } from 'react';

type Dept = { id: number; name: string };
type Pos = {
    id: number;
    title: string;
    description: string | null;
    level: number;
    department_id: number | null;
    department_name: string | null;
    is_active: boolean;
};

type Props = { positions: Pos[]; departments: Dept[] };

export default function Positions({ positions, departments }: Props) {
    const [editing, setEditing] = useState<Pos | null>(null);

    const create = useForm({ title: '', description: '', level: '0', department_id: '' });
    const edit = useForm({ title: editing?.title ?? '', description: editing?.description ?? '', level: editing?.level?.toString() ?? '0', department_id: editing?.department_id?.toString() ?? '' });
    const del = useForm({});

    function startEdit(p: Pos) {
        setEditing(p);
        edit.setData({ title: p.title, description: p.description ?? '', level: p.level.toString(), department_id: p.department_id?.toString() ?? '' });
    }

    function submitCreate(e: React.FormEvent) {
        e.preventDefault();
        create.post('/positions', { onSuccess: () => create.reset() });
    }

    function submitEdit(e: React.FormEvent) {
        e.preventDefault();
        if (!editing) return;
        edit.patch(`/positions/${editing.id}`, { onSuccess: () => setEditing(null) });
    }

    function destroy(p: Pos) {
        if (!confirm(`Delete "${p.title}"?`)) return;
        del.delete(`/positions/${p.id}`);
    }

    return (
        <>
            <Head title="Positions" />
            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Add position">
                    <form onSubmit={submitCreate} className="max-w-md space-y-4 mt-6">
                        <Field label="Title" error={create.errors.title}>
                            <input value={create.data.title} onChange={e => create.setData('title', e.target.value)} className="input-base" placeholder="e.g. Senior Engineer" />
                        </Field>
                        <Field label="Department" error={create.errors.department_id}>
                            <select value={create.data.department_id} onChange={e => create.setData('department_id', e.target.value)} className="input-base">
                                <option value="">— Select department —</option>
                                {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </select>
                        </Field>
                        <Field label="Level (0 = junior, 10 = executive)" error={create.errors.level}>
                            <input type="number" min={0} max={10} value={create.data.level} onChange={e => create.setData('level', e.target.value)} className="input-base w-24" />
                        </Field>
                        <Field label="Description" error={create.errors.description}>
                            <input value={create.data.description} onChange={e => create.setData('description', e.target.value)} className="input-base" />
                        </Field>
                        <button type="submit" disabled={create.processing}
                            className="inline-flex min-h-11 items-center gap-3 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                            Add position
                        </button>
                    </form>
                </Section>

                {editing && (
                    <Section number="01" label={`Edit — ${editing.title}`}>
                        <form onSubmit={submitEdit} className="max-w-md space-y-4 mt-6">
                            <Field label="Title" error={edit.errors.title}>
                                <input value={edit.data.title} onChange={e => edit.setData('title', e.target.value)} className="input-base" />
                            </Field>
                            <Field label="Department" error={edit.errors.department_id}>
                                <select value={edit.data.department_id} onChange={e => edit.setData('department_id', e.target.value)} className="input-base">
                                    <option value="">— None —</option>
                                    {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                                </select>
                            </Field>
                            <Field label="Level" error={edit.errors.level}>
                                <input type="number" min={0} max={10} value={edit.data.level} onChange={e => edit.setData('level', e.target.value)} className="input-base w-24" />
                            </Field>
                            <div className="flex gap-2">
                                <button type="submit" disabled={edit.processing}
                                    className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                                    Save
                                </button>
                                <button type="button" onClick={() => setEditing(null)}
                                    className="inline-flex min-h-11 items-center gap-2 border border-yzh-bone-soft px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-text hover:border-yzh-ink">
                                    <X className="h-3 w-3" /> Cancel
                                </button>
                            </div>
                        </form>
                    </Section>
                )}

                <Section number={editing ? '02' : '01'} label={`${positions.length} positions`}>
                    {positions.length === 0 ? (
                        <p className="mt-6 text-sm text-yzh-slate">No positions yet.</p>
                    ) : (
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {positions.map(p => (
                                <li key={p.id} className="flex items-center justify-between gap-4 py-4">
                                    <div>
                                        <span className="text-base font-semibold text-yzh-ink">{p.title}</span>
                                        <span className="ml-2 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">L{p.level}</span>
                                        {p.department_name && (
                                            <span className="ml-2 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-gold">{p.department_name}</span>
                                        )}
                                    </div>
                                    <div className="flex shrink-0 gap-1">
                                        <button onClick={() => startEdit(p)} className="inline-flex h-9 w-9 items-center justify-center text-yzh-text hover:text-yzh-ink">
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                        <button onClick={() => destroy(p)} disabled={del.processing} className="inline-flex h-9 w-9 items-center justify-center text-yzh-text hover:text-red-600 disabled:opacity-40">
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </Section>
            </div>
        </>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return (
        <div>
            <label className="mb-1 block font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">{label}</label>
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
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

Positions.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">S.02 / Settings / Positions</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Positions.</h1>
        </div>
    }>{page}</AppLayout>
);
