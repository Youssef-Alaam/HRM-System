import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Trash2, X } from 'lucide-react';
import { ReactNode, useState } from 'react';

type Dept = {
    id: number;
    name: string;
    description: string | null;
    parent_department_id: number | null;
    parent_name: string | null;
    is_active: boolean;
};

type Props = { departments: Dept[] };

export default function Departments({ departments }: Props) {
    const [editing, setEditing] = useState<Dept | null>(null);

    const create = useForm({ name: '', description: '', parent_department_id: '' });
    const edit = useForm({ name: editing?.name ?? '', description: editing?.description ?? '', parent_department_id: editing?.parent_department_id?.toString() ?? '' });
    const del = useForm({});

    function startEdit(d: Dept) {
        setEditing(d);
        edit.setData({ name: d.name, description: d.description ?? '', parent_department_id: d.parent_department_id?.toString() ?? '' });
    }

    function submitCreate(e: React.FormEvent) {
        e.preventDefault();
        create.post('/departments', { onSuccess: () => create.reset() });
    }

    function submitEdit(e: React.FormEvent) {
        e.preventDefault();
        if (!editing) return;
        edit.patch(`/departments/${editing.id}`, { onSuccess: () => setEditing(null) });
    }

    function destroy(d: Dept) {
        if (!confirm(`Delete "${d.name}"? This cannot be undone if it has sub-departments.`)) return;
        del.delete(`/departments/${d.id}`);
    }

    return (
        <>
            <Head title="Departments" />
            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Add department">
                    <form onSubmit={submitCreate} className="max-w-md space-y-4 mt-6">
                        <Field label="Name" error={create.errors.name}>
                            <input value={create.data.name} onChange={e => create.setData('name', e.target.value)}
                                className="input-base" placeholder="e.g. Engineering" />
                        </Field>
                        <Field label="Description" error={create.errors.description}>
                            <input value={create.data.description} onChange={e => create.setData('description', e.target.value)}
                                className="input-base" placeholder="Optional" />
                        </Field>
                        <Field label="Parent department" error={create.errors.parent_department_id}>
                            <select value={create.data.parent_department_id} onChange={e => create.setData('parent_department_id', e.target.value)}
                                className="input-base">
                                <option value="">— None (top-level) —</option>
                                {departments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </select>
                        </Field>
                        <button type="submit" disabled={create.processing}
                            className="inline-flex min-h-11 items-center gap-3 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                            Add department
                        </button>
                    </form>
                </Section>

                {editing && (
                    <Section number="01" label={`Edit — ${editing.name}`}>
                        <form onSubmit={submitEdit} className="max-w-md space-y-4 mt-6">
                            <Field label="Name" error={edit.errors.name}>
                                <input value={edit.data.name} onChange={e => edit.setData('name', e.target.value)} className="input-base" />
                            </Field>
                            <Field label="Description" error={edit.errors.description}>
                                <input value={edit.data.description} onChange={e => edit.setData('description', e.target.value)} className="input-base" />
                            </Field>
                            <div className="flex gap-2">
                                <button type="submit" disabled={edit.processing}
                                    className="inline-flex min-h-11 items-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
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

                <Section number={editing ? '02' : '01'} label={`${departments.length} departments`}>
                    {departments.length === 0 ? (
                        <p className="mt-6 text-sm text-yzh-slate">No departments yet.</p>
                    ) : (
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {departments.map(d => (
                                <li key={d.id} className="flex items-center justify-between gap-4 py-4">
                                    <div>
                                        <span className="text-base font-semibold text-yzh-ink">{d.name}</span>
                                        {d.parent_name && (
                                            <span className="ml-2 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">↳ {d.parent_name}</span>
                                        )}
                                        {d.description && <p className="mt-0.5 text-sm text-yzh-slate">{d.description}</p>}
                                    </div>
                                    <div className="flex shrink-0 gap-1">
                                        <button onClick={() => startEdit(d)} className="inline-flex h-9 w-9 items-center justify-center rounded-md text-yzh-text hover:text-yzh-ink focus-visible:ring-2 focus-visible:ring-yzh-gold">
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                        <button onClick={() => destroy(d)} disabled={del.processing} className="inline-flex h-9 w-9 items-center justify-center rounded-md text-yzh-text hover:text-red-600 focus-visible:ring-2 focus-visible:ring-yzh-gold disabled:opacity-40">
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

Departments.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">S.01 / Settings / Departments</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Departments.</h1>
        </div>
    }>{page}</AppLayout>
);
