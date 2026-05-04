import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight } from 'lucide-react';
import { FormEventHandler, ReactNode } from 'react';

type Props = {
    categories: { id: number; name: string }[];
};

function Create({ categories }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        asset_category_id: '' as number | '',
        name: '',
        serial_number: '',
        model: '',
        // Stored in piasters; the form collects EGP and converts on submit
        // by passing value_piasters directly so the backend trusts the
        // FormRequest validator (no client-side conversion needed when we
        // already operate in piasters here).
        value_piasters: 0,
        acquired_date: '',
        condition_at_acquisition: 'new' as 'new' | 'used' | 'refurbished',
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/assets', { preserveScroll: true });
    };

    return (
        <>
            <Head title="New asset" />

            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Asset details">
                    <form
                        onSubmit={submit}
                        className="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2"
                    >
                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="asset_category_id"
                                value="Category"
                            />
                            <select
                                id="asset_category_id"
                                value={data.asset_category_id}
                                onChange={(e) =>
                                    setData(
                                        'asset_category_id',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                className="block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                                required
                            >
                                <option value="">Select category</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.asset_category_id} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel htmlFor="name" value="Name" />
                            <TextInput
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                placeholder="Dell XPS 15"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="serial_number"
                                value="Serial number (optional)"
                            />
                            <TextInput
                                id="serial_number"
                                value={data.serial_number}
                                onChange={(e) =>
                                    setData('serial_number', e.target.value)
                                }
                            />
                            <InputError message={errors.serial_number} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel htmlFor="model" value="Model (optional)" />
                            <TextInput
                                id="model"
                                value={data.model}
                                onChange={(e) =>
                                    setData('model', e.target.value)
                                }
                            />
                            <InputError message={errors.model} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="value_piasters"
                                value="Value (in piasters · 1 EGP = 100)"
                            />
                            <TextInput
                                id="value_piasters"
                                type="number"
                                min={0}
                                value={data.value_piasters}
                                onChange={(e) =>
                                    setData(
                                        'value_piasters',
                                        Number(e.target.value),
                                    )
                                }
                                required
                            />
                            <InputError message={errors.value_piasters} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="acquired_date"
                                value="Acquired date (optional)"
                            />
                            <TextInput
                                id="acquired_date"
                                type="date"
                                value={data.acquired_date}
                                onChange={(e) =>
                                    setData('acquired_date', e.target.value)
                                }
                            />
                            <InputError message={errors.acquired_date} />
                        </div>

                        <div className="space-y-2">
                            <InputLabel
                                htmlFor="condition_at_acquisition"
                                value="Condition"
                            />
                            <select
                                id="condition_at_acquisition"
                                value={data.condition_at_acquisition}
                                onChange={(e) =>
                                    setData(
                                        'condition_at_acquisition',
                                        e.target.value as
                                            | 'new'
                                            | 'used'
                                            | 'refurbished',
                                    )
                                }
                                className="block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                            >
                                <option value="new">New</option>
                                <option value="used">Used</option>
                                <option value="refurbished">Refurbished</option>
                            </select>
                            <InputError
                                message={errors.condition_at_acquisition}
                            />
                        </div>

                        <div className="space-y-2 sm:col-span-2">
                            <InputLabel htmlFor="notes" value="Notes (optional)" />
                            <TextInput
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="sm:col-span-2 flex flex-wrap items-center gap-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span>
                                    {processing ? 'Creating' : 'Create asset'}
                                </span>
                                <ArrowUpRight
                                    className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                    aria-hidden="true"
                                />
                            </button>
                            <Link
                                href="/assets"
                                className="inline-flex min-h-11 items-center gap-2 rounded font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold"
                            >
                                <ArrowLeft
                                    className="h-3.5 w-3.5"
                                    aria-hidden="true"
                                />
                                Cancel
                            </Link>
                        </div>
                    </form>
                </Section>
            </div>
        </>
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

Create.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    B.04 / People / Assets / New
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    New asset.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Create;
