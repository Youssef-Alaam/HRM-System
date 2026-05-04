import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight } from 'lucide-react';
import { FormEventHandler, ReactNode, useMemo } from 'react';

type Department = { id: number; name: string };
type Position = { id: number; title: string; department_id: number | null };
type Office = { id: number; name: string };
type Manager = { id: number; name: string };

type Props = {
    departments: Department[];
    positions: Position[];
    offices: Office[];
    managers: Manager[];
};

type FormShape = {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    national_id: string;
    date_of_birth: string;
    gender: '' | 'male' | 'female' | 'other';
    marital_status: '' | 'single' | 'married' | 'divorced' | 'widowed';
    dependents: number;
    nationality: string;
    address: string;
    emergency_contact_name: string;
    emergency_contact_phone: string;
    department_id: number | '';
    position_id: number | '';
    office_id: number | '';
    manager_id: number | '';
    hiring_date: string;
    contract_type:
        | 'probation'
        | 'fixed'
        | 'unlimited'
        | 'part_time'
        | 'internship'
        | 'project';
    contract_start_date: string;
    contract_end_date: string;
    base_salary_piasters: number;
    is_expat: boolean;
    passport_number: string;
    passport_expiry: string;
    work_permit_number: string;
    work_permit_expiry: string;
};

function Create({ departments, positions, offices, managers }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormShape>({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        national_id: '',
        date_of_birth: '',
        gender: '',
        marital_status: 'single',
        dependents: 0,
        nationality: 'Egyptian',
        address: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        department_id: '',
        position_id: '',
        office_id: '',
        manager_id: '',
        hiring_date: '',
        contract_type: 'probation',
        contract_start_date: '',
        contract_end_date: '',
        base_salary_piasters: 0,
        is_expat: false,
        passport_number: '',
        passport_expiry: '',
        work_permit_number: '',
        work_permit_expiry: '',
    });

    // Filter positions to the selected department so the picker stays
    // coherent. If no department is selected yet, show all positions.
    const filteredPositions = useMemo(() => {
        if (!data.department_id) return positions;
        return positions.filter((p) => p.department_id === data.department_id);
    }, [positions, data.department_id]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/employees');
    };

    return (
        <>
            <Head title="New employee" />

            <form onSubmit={submit} className="space-y-12 sm:space-y-16">
                <Section number="00" label="Identity">
                    <Field>
                        <InputLabel htmlFor="first_name" value="First name" />
                        <TextInput
                            id="first_name"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            required
                            autoComplete="given-name"
                            isFocused
                        />
                        <InputError message={errors.first_name} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="last_name" value="Last name" />
                        <TextInput
                            id="last_name"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            required
                            autoComplete="family-name"
                        />
                        <InputError message={errors.last_name} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            autoComplete="email"
                        />
                        <InputError message={errors.email} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="phone" value="Phone" />
                        <TextInput
                            id="phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            required
                            placeholder="01012345678"
                            inputMode="tel"
                        />
                        <InputError message={errors.phone} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="national_id" value="National ID" />
                        <TextInput
                            id="national_id"
                            value={data.national_id}
                            onChange={(e) => setData('national_id', e.target.value)}
                            required
                            placeholder="14 digits"
                            inputMode="numeric"
                            maxLength={14}
                        />
                        <InputError message={errors.national_id} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="date_of_birth" value="Date of birth" />
                        <TextInput
                            id="date_of_birth"
                            type="date"
                            value={data.date_of_birth}
                            onChange={(e) => setData('date_of_birth', e.target.value)}
                            required
                        />
                        <InputError message={errors.date_of_birth} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="gender" value="Gender" />
                        <Select
                            id="gender"
                            value={data.gender}
                            onChange={(e) =>
                                setData('gender', e.target.value as FormShape['gender'])
                            }
                            required
                        >
                            <option value="" disabled>
                                Select…
                            </option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </Select>
                        <InputError message={errors.gender} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="nationality" value="Nationality" />
                        <TextInput
                            id="nationality"
                            value={data.nationality}
                            onChange={(e) => setData('nationality', e.target.value)}
                            required
                        />
                        <InputError message={errors.nationality} />
                    </Field>
                </Section>

                <Section number="01" label="Personal">
                    <Field>
                        <InputLabel
                            htmlFor="marital_status"
                            value="Marital status"
                        />
                        <Select
                            id="marital_status"
                            value={data.marital_status}
                            onChange={(e) =>
                                setData(
                                    'marital_status',
                                    e.target.value as FormShape['marital_status'],
                                )
                            }
                            required
                        >
                            <option value="single">Single</option>
                            <option value="married">Married</option>
                            <option value="divorced">Divorced</option>
                            <option value="widowed">Widowed</option>
                        </Select>
                        <InputError message={errors.marital_status} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="dependents" value="Dependents" />
                        <TextInput
                            id="dependents"
                            type="number"
                            min={0}
                            max={30}
                            value={data.dependents}
                            onChange={(e) =>
                                setData('dependents', Number(e.target.value))
                            }
                        />
                        <InputError message={errors.dependents} />
                    </Field>
                    <Field full>
                        <InputLabel htmlFor="address" value="Address" />
                        <TextInput
                            id="address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            autoComplete="street-address"
                        />
                        <InputError message={errors.address} />
                    </Field>
                    <Field>
                        <InputLabel
                            htmlFor="emergency_contact_name"
                            value="Emergency contact name"
                        />
                        <TextInput
                            id="emergency_contact_name"
                            value={data.emergency_contact_name}
                            onChange={(e) =>
                                setData('emergency_contact_name', e.target.value)
                            }
                        />
                        <InputError message={errors.emergency_contact_name} />
                    </Field>
                    <Field>
                        <InputLabel
                            htmlFor="emergency_contact_phone"
                            value="Emergency contact phone"
                        />
                        <TextInput
                            id="emergency_contact_phone"
                            value={data.emergency_contact_phone}
                            onChange={(e) =>
                                setData('emergency_contact_phone', e.target.value)
                            }
                            placeholder="01012345678"
                            inputMode="tel"
                        />
                        <InputError message={errors.emergency_contact_phone} />
                    </Field>
                </Section>

                <Section number="02" label="Assignment">
                    <Field>
                        <InputLabel htmlFor="department_id" value="Department" />
                        <Select
                            id="department_id"
                            value={data.department_id}
                            onChange={(e) => {
                                const id = e.target.value === '' ? '' : Number(e.target.value);
                                setData('department_id', id);
                                // Reset position if it's not in the new department.
                                if (
                                    id !== '' &&
                                    data.position_id !== '' &&
                                    !positions.find(
                                        (p) =>
                                            p.id === data.position_id &&
                                            p.department_id === id,
                                    )
                                ) {
                                    setData('position_id', '');
                                }
                            }}
                            required
                        >
                            <option value="" disabled>
                                Select…
                            </option>
                            {departments.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.department_id} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="position_id" value="Position" />
                        <Select
                            id="position_id"
                            value={data.position_id}
                            onChange={(e) =>
                                setData(
                                    'position_id',
                                    e.target.value === '' ? '' : Number(e.target.value),
                                )
                            }
                            required
                            disabled={data.department_id === ''}
                        >
                            <option value="" disabled>
                                {data.department_id === ''
                                    ? 'Pick a department first'
                                    : 'Select…'}
                            </option>
                            {filteredPositions.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.title}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.position_id} />
                    </Field>
                    <Field>
                        <InputLabel htmlFor="office_id" value="Office" />
                        <Select
                            id="office_id"
                            value={data.office_id}
                            onChange={(e) =>
                                setData(
                                    'office_id',
                                    e.target.value === '' ? '' : Number(e.target.value),
                                )
                            }
                            required
                        >
                            <option value="" disabled>
                                Select…
                            </option>
                            {offices.map((o) => (
                                <option key={o.id} value={o.id}>
                                    {o.name}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.office_id} />
                    </Field>
                    <Field>
                        <InputLabel
                            htmlFor="manager_id"
                            value="Manager (optional)"
                        />
                        <Select
                            id="manager_id"
                            value={data.manager_id}
                            onChange={(e) =>
                                setData(
                                    'manager_id',
                                    e.target.value === '' ? '' : Number(e.target.value),
                                )
                            }
                        >
                            <option value="">No manager (auto-approves own requests)</option>
                            {managers.map((m) => (
                                <option key={m.id} value={m.id}>
                                    {m.name}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.manager_id} />
                    </Field>
                </Section>

                <Section number="03" label="Contract">
                    <Field>
                        <InputLabel htmlFor="hiring_date" value="Hire date" />
                        <TextInput
                            id="hiring_date"
                            type="date"
                            value={data.hiring_date}
                            onChange={(e) =>
                                setData('hiring_date', e.target.value)
                            }
                            required
                        />
                        <InputError message={errors.hiring_date} />
                    </Field>
                    <Field>
                        <InputLabel
                            htmlFor="contract_type"
                            value="Contract type"
                        />
                        <Select
                            id="contract_type"
                            value={data.contract_type}
                            onChange={(e) =>
                                setData(
                                    'contract_type',
                                    e.target.value as FormShape['contract_type'],
                                )
                            }
                            required
                        >
                            <option value="probation">Probation</option>
                            <option value="fixed">Fixed term</option>
                            <option value="unlimited">Unlimited</option>
                            <option value="part_time">Part time</option>
                            <option value="internship">Internship</option>
                            <option value="project">Project</option>
                        </Select>
                        <InputError message={errors.contract_type} />
                    </Field>
                    <Field>
                        <InputLabel
                            htmlFor="contract_start_date"
                            value="Contract start"
                        />
                        <TextInput
                            id="contract_start_date"
                            type="date"
                            value={data.contract_start_date}
                            onChange={(e) =>
                                setData('contract_start_date', e.target.value)
                            }
                        />
                        <InputError message={errors.contract_start_date} />
                    </Field>
                    <Field>
                        <InputLabel
                            htmlFor="contract_end_date"
                            value="Contract end"
                        />
                        <TextInput
                            id="contract_end_date"
                            type="date"
                            value={data.contract_end_date}
                            onChange={(e) =>
                                setData('contract_end_date', e.target.value)
                            }
                        />
                        <InputError message={errors.contract_end_date} />
                    </Field>
                    <Field>
                        <InputLabel
                            htmlFor="base_salary_piasters"
                            value="Base salary (EGP)"
                        />
                        <TextInput
                            id="base_salary_piasters"
                            type="number"
                            min={0}
                            step={1}
                            value={Math.floor(data.base_salary_piasters / 100)}
                            onChange={(e) =>
                                setData(
                                    'base_salary_piasters',
                                    Math.max(0, Number(e.target.value)) * 100,
                                )
                            }
                            required
                            placeholder="e.g. 15000"
                        />
                        <p className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                            Stored as piasters · 1 EGP = 100 piasters
                        </p>
                        <InputError message={errors.base_salary_piasters} />
                    </Field>
                </Section>

                <Section number="04" label="Expat">
                    <Field full>
                        <label className="inline-flex items-center gap-3 text-sm text-yzh-ink">
                            <input
                                type="checkbox"
                                checked={data.is_expat}
                                onChange={(e) =>
                                    setData('is_expat', e.target.checked)
                                }
                                className="h-4 w-4 border-yzh-bone-soft text-yzh-gold focus:ring-yzh-gold"
                            />
                            <span>Foreign national (passport + work permit required)</span>
                        </label>
                        <InputError message={errors.is_expat} />
                    </Field>
                    {data.is_expat && (
                        <>
                            <Field>
                                <InputLabel
                                    htmlFor="passport_number"
                                    value="Passport number"
                                />
                                <TextInput
                                    id="passport_number"
                                    value={data.passport_number}
                                    onChange={(e) =>
                                        setData('passport_number', e.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.passport_number} />
                            </Field>
                            <Field>
                                <InputLabel
                                    htmlFor="passport_expiry"
                                    value="Passport expiry"
                                />
                                <TextInput
                                    id="passport_expiry"
                                    type="date"
                                    value={data.passport_expiry}
                                    onChange={(e) =>
                                        setData('passport_expiry', e.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.passport_expiry} />
                            </Field>
                            <Field>
                                <InputLabel
                                    htmlFor="work_permit_number"
                                    value="Work permit number"
                                />
                                <TextInput
                                    id="work_permit_number"
                                    value={data.work_permit_number}
                                    onChange={(e) =>
                                        setData('work_permit_number', e.target.value)
                                    }
                                    required
                                />
                                <InputError message={errors.work_permit_number} />
                            </Field>
                            <Field>
                                <InputLabel
                                    htmlFor="work_permit_expiry"
                                    value="Work permit expiry"
                                />
                                <TextInput
                                    id="work_permit_expiry"
                                    type="date"
                                    value={data.work_permit_expiry}
                                    onChange={(e) =>
                                        setData('work_permit_expiry', e.target.value)
                                    }
                                    required
                                />
                                <InputError
                                    message={errors.work_permit_expiry}
                                />
                            </Field>
                        </>
                    )}
                </Section>

                <div className="flex flex-wrap items-center gap-5 border-t border-yzh-bone-soft pt-5">
                    <button
                        type="submit"
                        disabled={processing}
                        className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span>{processing ? 'Issuing' : 'Issue employee'}</span>
                        <ArrowUpRight
                            className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                            aria-hidden="true"
                        />
                    </button>
                    <Link
                        href="/employees"
                        className="inline-flex min-h-11 items-center gap-2 rounded font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                    >
                        <ArrowLeft className="h-3.5 w-3.5" aria-hidden="true" />
                        Cancel
                    </Link>
                </div>
            </form>
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
                <div className="mt-6 grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2 lg:grid-cols-3">
                    {children}
                </div>
            </div>
        </section>
    );
}

function Field({
    full = false,
    children,
}: {
    full?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={`space-y-2 ${full ? 'sm:col-span-2 lg:col-span-3' : ''}`}
        >
            {children}
        </div>
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
                'block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30 disabled:cursor-not-allowed disabled:bg-yzh-bone disabled:text-yzh-text ' +
                className
            }
        >
            {children}
        </select>
    );
}

Create.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    B.01 / People / New
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    New employee.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Create;
