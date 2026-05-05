import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { Pencil, Trash2, X } from 'lucide-react';
import { ReactNode, useState } from 'react';

type OfficeRow = {
    id: number;
    name: string;
    address: string | null;
    latitude: string | null;
    longitude: string | null;
    allowed_check_in_radius_meters: number;
    timezone: string;
    is_active: boolean;
};

type Props = { offices: OfficeRow[] };

const defaultForm = { name: '', address: '', latitude: '', longitude: '', allowed_check_in_radius_meters: '150', timezone: 'Africa/Cairo' };

export default function Offices({ offices }: Props) {
    const [editing, setEditing] = useState<OfficeRow | null>(null);

    const create = useForm({ ...defaultForm });
    const edit = useForm({ ...defaultForm });
    const del = useForm({});

    function startEdit(o: OfficeRow) {
        setEditing(o);
        edit.setData({
            name: o.name, address: o.address ?? '',
            latitude: o.latitude ?? '', longitude: o.longitude ?? '',
            allowed_check_in_radius_meters: o.allowed_check_in_radius_meters.toString(),
            timezone: o.timezone,
        });
    }

    function submitCreate(e: React.FormEvent) {
        e.preventDefault();
        create.post('/offices', { onSuccess: () => create.reset() });
    }

    function submitEdit(e: React.FormEvent) {
        e.preventDefault();
        if (!editing) return;
        edit.patch(`/offices/${editing.id}`, { onSuccess: () => setEditing(null) });
    }

    function destroy(o: OfficeRow) {
        if (!confirm(`Delete office "${o.name}"?`)) return;
        del.delete(`/offices/${o.id}`);
    }

    return (
        <>
            <Head title="Offices" />
            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Add office">
                    <form onSubmit={submitCreate} className="max-w-md space-y-4 mt-6">
                        <OfficeFields data={create.data} errors={create.errors} setData={create.setData} />
                        <button type="submit" disabled={create.processing}
                            className="inline-flex min-h-11 items-center border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50">
                            Add office
                        </button>
                    </form>
                </Section>

                {editing && (
                    <Section number="01" label={`Edit — ${editing.name}`}>
                        <form onSubmit={submitEdit} className="max-w-md space-y-4 mt-6">
                            <OfficeFields data={edit.data} errors={edit.errors} setData={edit.setData} />
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

                <Section number={editing ? '02' : '01'} label={`${offices.length} offices`}>
                    {offices.length === 0 ? (
                        <p className="mt-6 text-sm text-yzh-slate">No offices yet.</p>
                    ) : (
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {offices.map(o => (
                                <li key={o.id} className="flex items-start justify-between gap-4 py-4">
                                    <div>
                                        <span className="text-base font-semibold text-yzh-ink">{o.name}</span>
                                        <span className="ml-2 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">R{o.allowed_check_in_radius_meters}m</span>
                                        {o.address && <p className="mt-0.5 text-sm text-yzh-slate">{o.address}</p>}
                                        {o.latitude && (
                                            <p className="mt-0.5 font-mono text-[0.6875rem] text-yzh-text">{o.latitude}, {o.longitude}</p>
                                        )}
                                    </div>
                                    <div className="flex shrink-0 gap-1">
                                        <button onClick={() => startEdit(o)} className="inline-flex h-9 w-9 items-center justify-center text-yzh-text hover:text-yzh-ink">
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                        <button onClick={() => destroy(o)} disabled={del.processing} className="inline-flex h-9 w-9 items-center justify-center text-yzh-text hover:text-red-600 disabled:opacity-40">
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

function OfficeFields({ data, errors, setData }: { data: Record<string, string>; errors: Record<string, string>; setData: (k: string, v: string) => void }) {
    return (<>
        <Field label="Name" error={errors.name}>
            <input value={data.name} onChange={e => setData('name', e.target.value)} className="input-base" placeholder="e.g. Cairo HQ" />
        </Field>
        <Field label="Address" error={errors.address}>
            <input value={data.address} onChange={e => setData('address', e.target.value)} className="input-base" />
        </Field>
        <div className="grid grid-cols-2 gap-4">
            <Field label="Latitude" error={errors.latitude}>
                <input type="number" step="any" value={data.latitude} onChange={e => setData('latitude', e.target.value)} className="input-base" placeholder="30.0444" />
            </Field>
            <Field label="Longitude" error={errors.longitude}>
                <input type="number" step="any" value={data.longitude} onChange={e => setData('longitude', e.target.value)} className="input-base" placeholder="31.2357" />
            </Field>
        </div>
        <Field label="Check-in radius (metres)" error={errors.allowed_check_in_radius_meters}>
            <input type="number" min={10} max={5000} value={data.allowed_check_in_radius_meters} onChange={e => setData('allowed_check_in_radius_meters', e.target.value)} className="input-base w-32" />
        </Field>
        <Field label="Timezone" error={errors.timezone}>
            <input value={data.timezone} onChange={e => setData('timezone', e.target.value)} className="input-base" />
        </Field>
    </>);
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

Offices.layout = (page: ReactNode) => (
    <AppLayout header={
        <div className="flex flex-col gap-2">
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">S.03 / Settings / Offices</p>
            <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">Offices.</h1>
        </div>
    }>{page}</AppLayout>
);
