import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatMoneyEgp } from '@/lib/format';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight, Trash2 } from 'lucide-react';
import {
    FormEventHandler,
    ReactElement,
    ReactNode,
    useMemo,
    useState,
} from 'react';

type Asset = {
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
    notes: string | null;
    category: { id: number; name: string; icon_name: string } | null;
    current_employee: {
        id: number;
        employee_code: string;
        name: string;
    } | null;
};

type ChainRow = {
    id: number;
    employee: {
        id: number;
        employee_code: string;
        name: string;
    } | null;
    assigned_at: string | null;
    returned_at: string | null;
    expected_return_at: string | null;
    age_at_assignment_months: number;
    condition_at_assignment: string;
    return_condition: string | null;
    return_notes: string | null;
    assigned_by: string | null;
    returned_by: string | null;
    notes: string | null;
};

type AssignableEmployee = {
    id: number;
    employee_code: string;
    name: string;
};

type Props = {
    asset: Asset;
    chain: ChainRow[];
    assignableEmployees: AssignableEmployee[];
    canAssign: boolean;
    canDelete: boolean;
};

function Show({ asset, chain, assignableEmployees, canAssign, canDelete }: Props) {
    return (
        <>
            <Head title={asset.name} />

            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Identity">
                    <Field label="Name" value={asset.name} />
                    <Field
                        label="Category"
                        value={asset.category?.name ?? null}
                    />
                    <Field
                        label="Serial"
                        value={asset.serial_number}
                        mono
                    />
                    <Field label="Model" value={asset.model} />
                    <Field
                        label="Value"
                        value={formatMoneyEgp(asset.value_piasters)}
                        mono
                    />
                    <Field
                        label="Acquired"
                        value={
                            asset.acquired_date
                                ? formatDate(asset.acquired_date)
                                : null
                        }
                    />
                    <Field
                        label="Condition"
                        value={asset.condition_at_acquisition}
                        mono
                    />
                    <Field
                        label="Status"
                        value={asset.current_status.replace('_', ' ')}
                        mono
                    />
                </Section>

                <Section number="01" label="Current holder">
                    {asset.current_employee ? (
                        <div className="space-y-1">
                            <p className="text-base font-semibold text-yzh-ink">
                                {asset.current_employee.name}
                            </p>
                            <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                                {asset.current_employee.employee_code}
                            </p>
                        </div>
                    ) : (
                        <p className="text-sm text-yzh-slate">
                            Not currently assigned.
                        </p>
                    )}
                </Section>

                {canAssign && (
                    <Section number="02" label="Custody actions">
                        <CustodyActions
                            asset={asset}
                            assignableEmployees={assignableEmployees}
                            canDelete={canDelete}
                        />
                    </Section>
                )}

                <Section
                    number={canAssign ? '03' : '02'}
                    label={`Chain of custody · ${chain.length} entries`}
                >
                    {chain.length === 0 ? (
                        <p className="text-sm text-yzh-slate">
                            No assignments yet.
                        </p>
                    ) : (
                        <ul className="mt-4 divide-y divide-yzh-bone-soft border-t border-yzh-bone-soft">
                            {chain.map((row) => (
                                <ChainEntry key={row.id} row={row} />
                            ))}
                        </ul>
                    )}
                </Section>

                <div className="border-t border-yzh-bone-soft pt-5">
                    <Link
                        href="/assets"
                        className="inline-flex min-h-11 items-center gap-2 rounded font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                    >
                        <ArrowLeft className="h-3.5 w-3.5" aria-hidden="true" />
                        Back to inventory
                    </Link>
                </div>
            </div>
        </>
    );
}

function CustodyActions({
    asset,
    assignableEmployees,
    canDelete,
}: {
    asset: Asset;
    assignableEmployees: AssignableEmployee[];
    canDelete: boolean;
}) {
    const [tab, setTab] = useState<'assign' | 'return' | 'incident'>(
        asset.current_status === 'assigned' ? 'return' : 'assign',
    );

    return (
        <div className="space-y-6">
            <div
                className="flex flex-wrap gap-3"
                role="tablist"
                aria-label="Custody actions"
            >
                {(
                    [
                        ['assign', 'Assign'],
                        ['return', 'Return'],
                        ['incident', 'Mark lost / damaged'],
                    ] as const
                ).map(([key, label]) => (
                    <button
                        key={key}
                        type="button"
                        role="tab"
                        aria-selected={tab === key}
                        onClick={() => setTab(key)}
                        className={`min-h-11 border px-4 py-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] transition-colors ${
                            tab === key
                                ? 'border-yzh-gold bg-yzh-gold text-yzh-ink'
                                : 'border-yzh-bone-soft text-yzh-slate hover:border-yzh-gold hover:text-yzh-gold'
                        }`}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {tab === 'assign' && (
                <AssignForm
                    asset={asset}
                    assignableEmployees={assignableEmployees}
                />
            )}
            {tab === 'return' && <ReturnForm asset={asset} />}
            {tab === 'incident' && (
                <IncidentForm asset={asset} canDelete={canDelete} />
            )}
        </div>
    );
}

function AssignForm({
    asset,
    assignableEmployees,
}: {
    asset: Asset;
    assignableEmployees: AssignableEmployee[];
}) {
    const [employeeQuery, setEmployeeQuery] = useState('');
    const filteredEmployees = useMemo(() => {
        const term = employeeQuery.trim().toLowerCase();
        if (term === '') return assignableEmployees;
        return assignableEmployees.filter((emp) => {
            return (
                emp.name.toLowerCase().includes(term) ||
                emp.employee_code.toLowerCase().includes(term)
            );
        });
    }, [assignableEmployees, employeeQuery]);

    const { data, setData, post, processing, errors, reset } = useForm({
        employee_id: '' as number | '',
        condition_at_assignment: 'used' as 'new' | 'used' | 'refurbished',
        expected_return_at: '',
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/assets/${asset.id}/assign`, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setEmployeeQuery('');
            },
        });
    };

    return (
        <form
            onSubmit={submit}
            className="grid grid-cols-1 gap-x-8 gap-y-5 border-t border-yzh-bone-soft pt-5 sm:grid-cols-2"
        >
            <div className="space-y-2 sm:col-span-2">
                <InputLabel htmlFor="employee_search" value="Filter employees" />
                <TextInput
                    id="employee_search"
                    type="search"
                    value={employeeQuery}
                    onChange={(e) => setEmployeeQuery(e.target.value)}
                    placeholder="Search by name or employee code (e.g. EMP-10001)"
                />
            </div>

            <div className="space-y-2 sm:col-span-2">
                <InputLabel htmlFor="employee_id" value="Receiving employee" />
                <select
                    id="employee_id"
                    value={data.employee_id}
                    onChange={(e) =>
                        setData(
                            'employee_id',
                            e.target.value === '' ? '' : Number(e.target.value),
                        )
                    }
                    className="block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                    required
                >
                    <option value="">Select employee</option>
                    {filteredEmployees.map((emp) => (
                        <option key={emp.id} value={emp.id}>
                            {emp.employee_code} · {emp.name}
                        </option>
                    ))}
                </select>
                <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    {filteredEmployees.length} match
                    {filteredEmployees.length === 1 ? '' : 'es'}
                </p>
                <InputError message={errors.employee_id} />
            </div>
            <div className="space-y-2">
                <InputLabel
                    htmlFor="condition_at_assignment"
                    value="Condition"
                />
                <select
                    id="condition_at_assignment"
                    value={data.condition_at_assignment}
                    onChange={(e) =>
                        setData(
                            'condition_at_assignment',
                            e.target.value as 'new' | 'used' | 'refurbished',
                        )
                    }
                    className="block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                >
                    <option value="new">New</option>
                    <option value="used">Used</option>
                    <option value="refurbished">Refurbished</option>
                </select>
                <InputError message={errors.condition_at_assignment} />
            </div>
            <div className="space-y-2">
                <InputLabel
                    htmlFor="expected_return_at"
                    value="Expected return (loaner only)"
                />
                <TextInput
                    id="expected_return_at"
                    type="date"
                    value={data.expected_return_at}
                    onChange={(e) =>
                        setData('expected_return_at', e.target.value)
                    }
                />
                <InputError message={errors.expected_return_at} />
            </div>
            <div className="space-y-2 sm:col-span-2">
                <InputLabel htmlFor="notes" value="Notes (optional)" />
                <TextInput
                    id="notes"
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                />
                <InputError message={errors.notes} />
            </div>
            <div className="sm:col-span-2">
                <p className="mb-3 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    Age at assignment auto-derived from acquired date
                </p>
                <button
                    type="submit"
                    disabled={processing || data.employee_id === ''}
                    className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Assigning' : 'Assign'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </form>
    );
}

function ReturnForm({ asset }: { asset: Asset }) {
    const { data, setData, post, processing, errors } = useForm({
        return_condition: 'good' as 'good' | 'damaged' | 'lost',
        return_notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/assets/${asset.id}/return`, { preserveScroll: true });
    };

    if (asset.current_status !== 'assigned') {
        return (
            <p className="text-sm text-yzh-slate">
                Asset is not currently assigned.
            </p>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="grid grid-cols-1 gap-x-8 gap-y-5 border-t border-yzh-bone-soft pt-5 sm:grid-cols-2"
        >
            <div className="space-y-2">
                <InputLabel
                    htmlFor="return_condition"
                    value="Return condition"
                />
                <select
                    id="return_condition"
                    value={data.return_condition}
                    onChange={(e) =>
                        setData(
                            'return_condition',
                            e.target.value as 'good' | 'damaged' | 'lost',
                        )
                    }
                    className="block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                >
                    <option value="good">Good</option>
                    <option value="damaged">Damaged</option>
                    <option value="lost">Lost</option>
                </select>
                <InputError message={errors.return_condition} />
            </div>
            <div className="space-y-2 sm:col-span-2">
                <InputLabel
                    htmlFor="return_notes"
                    value="Return notes (required if damaged or lost)"
                />
                <TextInput
                    id="return_notes"
                    value={data.return_notes}
                    onChange={(e) => setData('return_notes', e.target.value)}
                />
                <InputError message={errors.return_notes} />
            </div>
            <div className="sm:col-span-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Returning' : 'Record return'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </form>
    );
}

function IncidentForm({
    asset,
    canDelete,
}: {
    asset: Asset;
    canDelete: boolean;
}) {
    const lostForm = useForm({ notes: '' });
    const damagedForm = useForm({ notes: '' });

    return (
        <div className="space-y-6 border-t border-yzh-bone-soft pt-5">
            <div className="space-y-3">
                <h4 className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    Mark lost
                </h4>
                <TextInput
                    placeholder="Required: explain when / where / how"
                    value={lostForm.data.notes}
                    onChange={(e) => lostForm.setData('notes', e.target.value)}
                />
                <InputError message={lostForm.errors.notes} />
                <button
                    type="button"
                    disabled={lostForm.processing || !lostForm.data.notes}
                    onClick={() =>
                        lostForm.post(`/assets/${asset.id}/mark-lost`, {
                            preserveScroll: true,
                            onSuccess: () => lostForm.reset(),
                        })
                    }
                    className="inline-flex min-h-10 items-center gap-2 border border-red-300 px-4 py-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-red-700 transition-colors hover:bg-red-50 disabled:opacity-50"
                >
                    Mark lost
                </button>
            </div>

            <div className="space-y-3">
                <h4 className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    Mark damaged
                </h4>
                <TextInput
                    placeholder="Required: describe damage"
                    value={damagedForm.data.notes}
                    onChange={(e) =>
                        damagedForm.setData('notes', e.target.value)
                    }
                />
                <InputError message={damagedForm.errors.notes} />
                <button
                    type="button"
                    disabled={
                        damagedForm.processing || !damagedForm.data.notes
                    }
                    onClick={() =>
                        damagedForm.post(`/assets/${asset.id}/mark-damaged`, {
                            preserveScroll: true,
                            onSuccess: () => damagedForm.reset(),
                        })
                    }
                    className="inline-flex min-h-10 items-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold transition-colors hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50"
                >
                    Mark damaged
                </button>
            </div>

            {canDelete && (
                <div className="space-y-3 border-t border-red-200 pt-5">
                    <h4 className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-red-700">
                        Danger zone
                    </h4>
                    <p className="text-sm text-yzh-slate">
                        Soft-deletes the asset. Blocked while currently
                        assigned — record a return first.
                    </p>
                    <button
                        type="button"
                        onClick={() => {
                            if (
                                confirm(
                                    `Delete asset "${asset.name}"? Soft-delete only — history preserved.`,
                                )
                            ) {
                                router.delete(`/assets/${asset.id}`);
                            }
                        }}
                        className="inline-flex min-h-10 items-center gap-2 border border-red-300 px-4 py-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-red-700 transition-colors hover:bg-red-50"
                    >
                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                        Delete asset
                    </button>
                </div>
            )}
        </div>
    );
}

function ChainEntry({ row }: { row: ChainRow }) {
    const isOpen = row.returned_at === null;

    return (
        <li className="grid grid-cols-1 gap-2 py-4 sm:grid-cols-3 sm:gap-4">
            <div>
                <p className="text-sm font-semibold text-yzh-ink">
                    {row.employee?.name ?? '—'}
                </p>
                <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    {row.employee?.employee_code ?? ''}
                </p>
            </div>
            <div className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate">
                {row.assigned_at && (
                    <span>Assigned {formatDate(row.assigned_at)}</span>
                )}
                {row.returned_at && (
                    <span>
                        {' · '}returned {formatDate(row.returned_at)} ·{' '}
                        {row.return_condition}
                    </span>
                )}
                {row.return_notes && (
                    <p className="mt-1 normal-case tracking-normal text-yzh-slate">
                        {row.return_notes}
                    </p>
                )}
            </div>
            <div
                className={`text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] ${isOpen ? 'text-yzh-gold' : 'text-yzh-text'}`}
            >
                {isOpen ? 'Currently held' : 'Closed'}
                <p className="normal-case tracking-normal text-yzh-slate">
                    age {row.age_at_assignment_months} mo @ assign
                </p>
            </div>
        </li>
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
                <dl className="mt-6 grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                    {children}
                </dl>
            </div>
        </section>
    );
}

function Field({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: string | number | null;
    mono?: boolean;
}) {
    return (
        <div>
            <dt className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                {label}
            </dt>
            <dd
                className={`mt-1 text-sm text-yzh-ink ${
                    mono ? 'font-mono' : ''
                }`}
            >
                {value ?? <span className="text-yzh-slate">—</span>}
            </dd>
        </div>
    );
}

Show.layout = (page: ReactElement<Props>) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    B.04 / People / Assets
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    {page.props.asset.name}.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Show;
