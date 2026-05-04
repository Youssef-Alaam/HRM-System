import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowUpRight, Trash2 } from 'lucide-react';
import { FormEventHandler, ReactNode, useState } from 'react';

type Category = {
    id: number;
    name: string;
    description: string | null;
    icon_name: string | null;
    order_index: number;
    is_active: boolean;
    assets_count: number;
};

type Props = {
    categories: Category[];
};

function AssetCategories({ categories }: Props) {
    const active = categories.filter((c) => c.is_active);
    const disabled = categories.filter((c) => !c.is_active);

    return (
        <>
            <Head title="Asset categories" />

            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Add new category">
                    <CreateForm />
                </Section>

                <Section number="01" label={`Active · ${active.length}`}>
                    {active.length === 0 ? (
                        <div className="mt-8 text-sm text-yzh-slate">
                            No active categories yet.
                        </div>
                    ) : (
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {active.map((category) => (
                                <Row key={category.id} category={category} />
                            ))}
                        </ul>
                    )}
                </Section>

                {disabled.length > 0 && (
                    <Section
                        number="02"
                        label={`Disabled · ${disabled.length}`}
                    >
                        <ul className="mt-6 divide-y divide-yzh-bone-soft">
                            {disabled.map((category) => (
                                <Row key={category.id} category={category} />
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
        description: '',
        icon_name: 'package',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/asset-categories', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2"
        >
            <div className="space-y-2">
                <InputLabel htmlFor="ac_name" value="Name" />
                <TextInput
                    id="ac_name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                    placeholder="e.g. Safety helmet"
                />
                <InputError message={errors.name} />
            </div>
            <div className="space-y-2">
                <InputLabel htmlFor="ac_icon" value="lucide-react icon name" />
                <TextInput
                    id="ac_icon"
                    value={data.icon_name}
                    onChange={(e) => setData('icon_name', e.target.value)}
                    placeholder="package"
                />
                <InputError message={errors.icon_name} />
            </div>
            <div className="space-y-2 sm:col-span-2">
                <InputLabel htmlFor="ac_desc" value="Description (optional)" />
                <TextInput
                    id="ac_desc"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
                <InputError message={errors.description} />
            </div>
            <div className="sm:col-span-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Adding' : 'Add category'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </form>
    );
}

function Row({ category }: { category: Category }) {
    const [editing, setEditing] = useState(false);

    if (editing) {
        return (
            <li className="py-5">
                <EditForm
                    category={category}
                    onCancel={() => setEditing(false)}
                />
            </li>
        );
    }

    return (
        <li className="flex flex-col gap-4 py-5 sm:flex-row sm:items-center sm:gap-6">
            <div className="flex-1 space-y-1">
                <div className="flex flex-wrap items-baseline gap-3">
                    <span className="text-base font-semibold text-yzh-ink">
                        {category.name}
                    </span>
                </div>
                <div className="flex flex-wrap gap-x-6 gap-y-1 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    <span>{category.assets_count} assets</span>
                    {category.icon_name && <span>icon: {category.icon_name}</span>}
                    {!category.is_active && (
                        <span className="text-red-700">Disabled</span>
                    )}
                </div>
                {category.description && (
                    <p className="text-sm text-yzh-slate">
                        {category.description}
                    </p>
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
                {category.is_active && category.assets_count === 0 && (
                    <button
                        type="button"
                        onClick={() => {
                            if (
                                confirm(
                                    `Disable category "${category.name}"?`,
                                )
                            ) {
                                router.delete(
                                    `/admin/asset-categories/${category.id}`,
                                    { preserveScroll: true },
                                );
                            }
                        }}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-md text-yzh-slate transition-colors hover:bg-red-50 hover:text-red-700"
                        aria-label="Disable category"
                    >
                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                    </button>
                )}
            </div>
        </li>
    );
}

function EditForm({
    category,
    onCancel,
}: {
    category: Category;
    onCancel: () => void;
}) {
    const { data, setData, patch, processing, errors } = useForm({
        name: category.name,
        description: category.description ?? '',
        icon_name: category.icon_name ?? 'package',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/admin/asset-categories/${category.id}`, {
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
                <InputLabel htmlFor={`ac_name_${category.id}`} value="Name" />
                <TextInput
                    id={`ac_name_${category.id}`}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                />
                <InputError message={errors.name} />
            </div>
            <div className="space-y-2">
                <InputLabel
                    htmlFor={`ac_icon_${category.id}`}
                    value="Icon name"
                />
                <TextInput
                    id={`ac_icon_${category.id}`}
                    value={data.icon_name}
                    onChange={(e) => setData('icon_name', e.target.value)}
                />
            </div>
            <div className="space-y-2 sm:col-span-2">
                <InputLabel
                    htmlFor={`ac_desc_${category.id}`}
                    value="Description"
                />
                <TextInput
                    id={`ac_desc_${category.id}`}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
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

AssetCategories.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    S.06 / Settings / Asset categories
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Asset categories.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default AssetCategories;
