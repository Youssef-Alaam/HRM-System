import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ReactElement, ReactNode } from 'react';

type Employee = {
    id: number;
    employee_code: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    national_id: string | null;
    date_of_birth: string | null;
    gender: string | null;
    marital_status: string | null;
    nationality: string;
    address: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    employment_status: string;
    hiring_date: string | null;
    contract_type: string;
    contract_start_date: string | null;
    contract_end_date: string | null;
    base_salary_piasters: number;
    is_expat: boolean;
    passport_number: string | null;
    work_permit_expiry: string | null;
    annual_leave_balance_days: string | number;
    sick_leave_balance_days: string | number;
    casual_leave_balance_days: string | number;
    department: { id: number; name: string } | null;
    position: { id: number; title: string } | null;
    office: { id: number; name: string } | null;
    manager: { id: number; first_name: string; last_name: string } | null;
};

type Props = {
    employee: Employee;
    canDelete: boolean;
};

function Show({ employee }: Props) {
    const fullName = `${employee.first_name} ${employee.last_name}`;

    return (
        <>
            <Head title={fullName} />

            <div className="space-y-12 sm:space-y-16">
                <Section number="00" label="Identity">
                    <Field label="Employee code" value={employee.employee_code} mono />
                    <Field label="Full name" value={fullName} />
                    <Field label="Email" value={employee.email} mono />
                    <Field label="Phone" value={employee.phone} mono />
                    <Field
                        label="National ID"
                        value={employee.national_id}
                        mono
                    />
                    <Field
                        label="Date of birth"
                        value={
                            employee.date_of_birth
                                ? formatDate(employee.date_of_birth)
                                : null
                        }
                    />
                    <Field label="Nationality" value={employee.nationality} />
                </Section>

                <Section number="01" label="Assignment">
                    <Field
                        label="Department"
                        value={employee.department?.name ?? null}
                    />
                    <Field
                        label="Position"
                        value={employee.position?.title ?? null}
                    />
                    <Field
                        label="Office"
                        value={employee.office?.name ?? null}
                    />
                    <Field
                        label="Manager"
                        value={
                            employee.manager
                                ? `${employee.manager.first_name} ${employee.manager.last_name}`
                                : null
                        }
                    />
                    <Field
                        label="Hire date"
                        value={
                            employee.hiring_date
                                ? formatDate(employee.hiring_date)
                                : null
                        }
                    />
                    <Field
                        label="Contract"
                        value={employee.contract_type}
                        mono
                    />
                    <Field
                        label="Status"
                        value={employee.employment_status}
                        mono
                    />
                </Section>

                <Section number="02" label="Leave balance">
                    <Field
                        label="Annual"
                        value={`${employee.annual_leave_balance_days} days`}
                    />
                    <Field
                        label="Sick"
                        value={`${employee.sick_leave_balance_days} days`}
                    />
                    <Field
                        label="Casual"
                        value={`${employee.casual_leave_balance_days} days`}
                    />
                </Section>

                {employee.is_expat && (
                    <Section number="03" label="Expat">
                        <Field
                            label="Passport"
                            value={employee.passport_number}
                            mono
                        />
                        <Field
                            label="Work permit expiry"
                            value={
                                employee.work_permit_expiry
                                    ? formatDate(employee.work_permit_expiry)
                                    : null
                            }
                        />
                    </Section>
                )}

                <div className="border-t border-yzh-bone-soft pt-5">
                    <Link
                        href="/employees"
                        className="inline-flex min-h-11 items-center gap-2 rounded font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                    >
                        <ArrowLeft className="h-3.5 w-3.5" aria-hidden="true" />
                        Back to roster
                    </Link>
                </div>
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
                    B.01 / People / {page.props.employee.employee_code}
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    {page.props.employee.first_name}{' '}
                    {page.props.employee.last_name}.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Show;
