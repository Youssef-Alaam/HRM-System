import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowUpRight, Trash2 } from 'lucide-react';
import { FormEventHandler, ReactNode, useState } from 'react';

type AppliesTo =
    | 'all'
    | 'egyptian_only'
    | 'expat_only'
    | 'egyptian_male_only';

type DocumentType = {
    id: number;
    name: string;
    name_ar: string | null;
    description: string | null;
    applies_to: AppliesTo;
    is_required: boolean;
    default_expiry_months: number | null;
    order_index: number;
    is_active: boolean;
};

type Props = {
    types: DocumentType[];
};

const APPLIES_TO_LABEL: Record<AppliesTo, string> = {
    all: 'All employees',
    egyptian_only: 'Egyptian only',
    expat_only: 'Expat only',
    egyptian_male_only: 'Egyptian male only',
};

function DocumentTypes({ types }: Props) {
    const active = types.filter((t) => t.is_active);
    const disabled = types.filter((t) => !t.is_active);

    return (
        <>
            <Head title="Document types" />

            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Add new type">
                    <CreateForm />
                </Section>

                <Section number="01" label={`Active matrix · ${active.length}`}>
                    {active.length === 0 ? (
                        <div className="mt-8 text-sm text-yzh-slate">
                            No active document types yet.
                        </div>
                    ) : (
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {active.map((type) => (
                                <TypeRow key={type.id} type={type} />
                            ))}
                        </ul>
                    )}
                </Section>

                {disabled.length > 0 && (
                    <Section number="02" label={`Disabled · ${disabled.length}`}>
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {disabled.map((type) => (
                                <TypeRow key={type.id} type={type} />
                            ))}
                        </ul>
                    </Section>
                )}
            </div>
        </>
    );
}

function CreateForm() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        name_ar: '',
        description: '',
        applies_to: 'all' as AppliesTo,
        is_required: true,
        default_expiry_months: '' as number | '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/document-types', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2"
        >
            <div className="space-y-2">
                <InputLabel htmlFor="dt_name" value="Name" />
                <TextInput
                    id="dt_name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                <InputError message={errors.name} />
            </div>
            <div className="space-y-2">
                <InputLabel htmlFor="dt_name_ar" value="Arabic name (optional)" />
                <TextInput
                    id="dt_name_ar"
                    value={data.name_ar}
                    onChange={(e) => setData('name_ar', e.target.value)}
                    dir="rtl"
                />
                <InputError message={errors.name_ar} />
            </div>
            <div className="space-y-2 sm:col-span-2">
                <InputLabel htmlFor="dt_description" value="Description" />
                <TextInput
                    id="dt_description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
                <InputError message={errors.description} />
            </div>
            <div className="space-y-2">
                <InputLabel htmlFor="dt_applies_to" value="Applies to" />
                <Select
                    id="dt_applies_to"
                    value={data.applies_to}
                    onChange={(e) =>
                        setData('applies_to', e.target.value as AppliesTo)
                    }
                >
                    <option value="all">All employees</option>
                    <option value="egyptian_only">Egyptian only</option>
                    <option value="expat_only">Expat only</option>
                    <option value="egyptian_male_only">Egyptian male only</option>
                </Select>
                <InputError message={errors.applies_to} />
            </div>
            <div className="space-y-2">
                <InputLabel
                    htmlFor="dt_default_expiry_months"
                    value="Default expiry (months)"
                />
                <TextInput
                    id="dt_default_expiry_months"
                    type="number"
                    min={1}
                    max={120}
                    value={data.default_expiry_months}
                    onChange={(e) =>
                        setData(
                            'default_expiry_months',
                            e.target.value === '' ? '' : Number(e.target.value),
                        )
                    }
                    placeholder="leave empty for no expiry"
                />
                <InputError message={errors.default_expiry_months} />
            </div>
            <div className="space-y-2 sm:col-span-2">
                <label className="inline-flex items-center gap-3 text-sm text-yzh-ink">
                    <input
                        type="checkbox"
                        checked={data.is_required}
                        onChange={(e) =>
                            setData('is_required', e.target.checked)
                        }
                        className="h-4 w-4 border-yzh-bone-soft text-yzh-gold focus:ring-yzh-gold"
                    />
                    <span>Required for all matched employees</span>
                </label>
                <InputError message={errors.is_required} />
            </div>
            <div className="sm:col-span-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Adding' : 'Add document type'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </form>
    );
}

function TypeRow({ type }: { type: DocumentType }) {
    const [editing, setEditing] = useState(false);

    if (editing) {
        return (
            <li className="py-5">
                <EditForm type={type} onCancel={() => setEditing(false)} />
            </li>
        );
    }

    return (
        <li className="flex flex-col gap-4 py-5 sm:flex-row sm:items-center sm:gap-6">
            <div className="flex-1 space-y-1">
                <div className="flex flex-wrap items-baseline gap-3">
                    <span className="text-base font-semibold text-yzh-ink">
                        {type.name}
                    </span>
                    {type.name_ar && (
                        <span className="text-sm text-yzh-slate" dir="rtl">
                            {type.name_ar}
                        </span>
                    )}
                </div>
                <div className="flex flex-wrap gap-x-6 gap-y-1 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    <span className={type.is_required ? 'text-yzh-gold' : ''}>
                        {type.is_required ? 'Required' : 'Optional'}
                    </span>
                    <span>{APPLIES_TO_LABEL[type.applies_to]}</span>
                    {type.default_expiry_months && (
                        <span>{type.default_expiry_months} mo expiry</span>
                    )}
                    {!type.is_active && (
                        <span className="text-red-700">Disabled</span>
                    )}
                </div>
                {type.description && (
                    <p className="text-sm text-yzh-slate">{type.description}</p>
                )}
            </div>
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    onClick={() => setEditing(true)}
                    className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate hover:text-yzh-gold"
                >
                    Edit
                </button>
                {type.is_active && (
                    <button
                        type="button"
                        onClick={() => {
                            if (
                                confirm(
                                    `Disable "${type.name}"? It will stop appearing in the required matrix but historical uploads remain linked.`,
                                )
                            ) {
                                router.delete(
                                    `/admin/document-types/${type.id}`,
                                    {
                                        preserveScroll: true,
                                    },
                                );
                            }
                        }}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-md text-yzh-slate transition-colors hover:bg-red-50 hover:text-red-700"
                        aria-label="Disable type"
                    >
                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                    </button>
                )}
            </div>
        </li>
    );
}

function EditForm({
    type,
    onCancel,
}: {
    type: DocumentType;
    onCancel: () => void;
}) {
    const { data, setData, patch, processing, errors } = useForm({
        name: type.name,
        name_ar: type.name_ar ?? '',
        description: type.description ?? '',
        applies_to: type.applies_to,
        is_required: type.is_required,
        default_expiry_months: type.default_expiry_months ?? ('' as number | ''),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/admin/document-types/${type.id}`, {
            preserveScroll: true,
            onSuccess: onCancel,
        });
    };

    return (
        <form
            onSubmit={submit}
            className="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2"
        >
            <div className="space-y-2">
                <InputLabel htmlFor={`dt_name_${type.id}`} value="Name" />
                <TextInput
                    id={`dt_name_${type.id}`}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                />
                <InputError message={errors.name} />
            </div>
            <div className="space-y-2">
                <InputLabel
                    htmlFor={`dt_applies_to_${type.id}`}
                    value="Applies to"
                />
                <Select
                    id={`dt_applies_to_${type.id}`}
                    value={data.applies_to}
                    onChange={(e) =>
                        setData('applies_to', e.target.value as AppliesTo)
                    }
                >
                    <option value="all">All employees</option>
                    <option value="egyptian_only">Egyptian only</option>
                    <option value="expat_only">Expat only</option>
                    <option value="egyptian_male_only">Egyptian male only</option>
                </Select>
            </div>
            <div className="space-y-2 sm:col-span-2">
                <label className="inline-flex items-center gap-3 text-sm text-yzh-ink">
                    <input
                        type="checkbox"
                        checked={data.is_required}
                        onChange={(e) =>
                            setData('is_required', e.target.checked)
                        }
                        className="h-4 w-4 border-yzh-bone-soft text-yzh-gold focus:ring-yzh-gold"
                    />
                    <span>Required</span>
                </label>
            </div>
            <div className="flex items-center gap-3 sm:col-span-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="inline-flex min-h-10 items-center justify-center gap-2 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors hover:bg-yzh-gold hover:text-yzh-ink disabled:opacity-50"
                >
                    <span>{processing ? 'Saving' : 'Save'}</span>
                </button>
                <button
                    type="button"
                    onClick={onCancel}
                    className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate hover:text-yzh-gold"
                >
                    Cancel
                </button>
            </div>
        </form>
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

DocumentTypes.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    S.05 / Settings / Document types
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Document types.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default DocumentTypes;
