import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { FormEventHandler } from 'react';

type EmployeeSelf = {
    id: number;
    phone: string | null;
    address: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    marital_status: 'single' | 'married' | 'divorced' | 'widowed' | null;
    dependents: number;
};

/**
 * Tier 1 self-edit (locked 2026-04-30 with Walid). Posts the Self-tier
 * fields to PATCH /employees/{employee_id}. The UpdateEmployeeRequest's
 * authorize() permits these keys for the employee themselves; HR/Admin
 * can edit the same fields on the same endpoint elsewhere.
 */
export default function UpdatePersonalInformationForm({
    employee,
    className = '',
}: {
    employee: EmployeeSelf;
    className?: string;
}) {
    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            phone: employee.phone ?? '',
            address: employee.address ?? '',
            emergency_contact_name: employee.emergency_contact_name ?? '',
            emergency_contact_phone: employee.emergency_contact_phone ?? '',
            marital_status: employee.marital_status ?? 'single',
            dependents: employee.dependents,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/employees/${employee.id}`, { preserveScroll: true });
    };

    return (
        <section className={className}>
            <h2 className="text-xl font-semibold tracking-tight text-yzh-ink">
                Personal.
            </h2>
            <p className="mt-2 text-sm leading-relaxed text-yzh-slate">
                Keep your contact info and household details current. Salary,
                position, and contract changes are HR-only.
            </p>

            <form
                onSubmit={submit}
                className="mt-8 grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2"
            >
                <div className="space-y-2">
                    <InputLabel htmlFor="profile_phone" value="Phone" />
                    <TextInput
                        id="profile_phone"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        placeholder="01012345678"
                        inputMode="tel"
                        autoComplete="tel"
                    />
                    <InputError message={errors.phone} />
                </div>
                <div className="space-y-2">
                    <InputLabel
                        htmlFor="profile_marital_status"
                        value="Marital status"
                    />
                    <select
                        id="profile_marital_status"
                        value={data.marital_status}
                        onChange={(e) =>
                            setData(
                                'marital_status',
                                e.target.value as typeof data.marital_status,
                            )
                        }
                        className="block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                    >
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                    </select>
                    <InputError message={errors.marital_status} />
                </div>
                <div className="space-y-2 sm:col-span-2">
                    <InputLabel htmlFor="profile_address" value="Address" />
                    <TextInput
                        id="profile_address"
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                        autoComplete="street-address"
                    />
                    <InputError message={errors.address} />
                </div>
                <div className="space-y-2">
                    <InputLabel
                        htmlFor="profile_emergency_contact_name"
                        value="Emergency contact name"
                    />
                    <TextInput
                        id="profile_emergency_contact_name"
                        value={data.emergency_contact_name}
                        onChange={(e) =>
                            setData('emergency_contact_name', e.target.value)
                        }
                    />
                    <InputError message={errors.emergency_contact_name} />
                </div>
                <div className="space-y-2">
                    <InputLabel
                        htmlFor="profile_emergency_contact_phone"
                        value="Emergency contact phone"
                    />
                    <TextInput
                        id="profile_emergency_contact_phone"
                        value={data.emergency_contact_phone}
                        onChange={(e) =>
                            setData('emergency_contact_phone', e.target.value)
                        }
                        placeholder="01012345678"
                        inputMode="tel"
                    />
                    <InputError message={errors.emergency_contact_phone} />
                </div>
                <div className="space-y-2">
                    <InputLabel
                        htmlFor="profile_dependents"
                        value="Dependents"
                    />
                    <TextInput
                        id="profile_dependents"
                        type="number"
                        min={0}
                        max={30}
                        value={data.dependents}
                        onChange={(e) =>
                            setData('dependents', Number(e.target.value))
                        }
                    />
                    <InputError message={errors.dependents} />
                </div>

                <div className="flex items-center gap-5 sm:col-span-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span>{processing ? 'Saving' : 'Save changes'}</span>
                        <ArrowUpRight
                            className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                            aria-hidden="true"
                        />
                    </button>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-out duration-150"
                        enterFrom="opacity-0"
                        leave="transition ease-in duration-150"
                        leaveTo="opacity-0"
                    >
                        <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                            Saved
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
