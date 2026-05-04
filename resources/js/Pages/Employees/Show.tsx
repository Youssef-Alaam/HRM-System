import FaceEnrollmentWizard from '@/Components/FaceEnrollmentWizard';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowUpRight,
    Download,
    ScanFace,
    Trash2,
} from 'lucide-react';
import { FormEventHandler, ReactElement, ReactNode, useState } from 'react';

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

type FacePayload = {
    has_descriptor: boolean;
    last_enrolled_at: string | null;
    months_since: number | null;
    cadence_months: number;
    failed_checkin_count: number;
    requires_reenrollment: boolean;
    min_quality_score: number;
    photo_count: number;
};

type AssetRow = {
    id: number;
    name: string;
    serial_number: string | null;
    value_piasters: number;
    current_status: string;
    category: { id: number; name: string } | null;
};

type AssetsPayload = {
    current: AssetRow[];
};

type AppliesTo =
    | 'all'
    | 'egyptian_only'
    | 'expat_only'
    | 'egyptian_male_only';

type DocumentTypeRow = {
    id: number;
    name: string;
    name_ar: string | null;
    applies_to: AppliesTo;
    is_required: boolean;
    applies_to_employee: boolean;
    default_expiry_months: number | null;
};

type UploadedDocument = {
    id: number;
    document_type_id: number | null;
    original_filename: string;
    mime_type: string;
    file_size_bytes: number;
    issued_date: string | null;
    expiry_date: string | null;
    is_expired: boolean;
    uploaded_at: string | null;
    uploaded_by: string | null;
    notes: string | null;
};

type DocumentsPayload = {
    matrix: DocumentTypeRow[];
    uploaded: UploadedDocument[];
    cap: number;
};

type Props = {
    employee: Employee;
    canDelete: boolean;
    canManageDocuments: boolean;
    documents: DocumentsPayload | null;
    assets: AssetsPayload | null;
    face: FacePayload | null;
    canEnrollFace: boolean;
    canResetFace: boolean;
};

function Show({
    employee,
    documents,
    canManageDocuments,
    assets,
    face,
    canEnrollFace,
    canResetFace,
}: Props) {
    const fullName = `${employee.first_name} ${employee.last_name}`;
    const [wizardOpen, setWizardOpen] = useState(false);

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

                {documents && (
                    <DocumentsSection
                        employeeId={employee.id}
                        documents={documents}
                        canManage={canManageDocuments}
                    />
                )}

                {assets && <AssetsSection assets={assets} />}

                {face && (
                    <FaceSection
                        employeeId={employee.id}
                        face={face}
                        canEnroll={canEnrollFace}
                        canReset={canResetFace}
                        onLaunchWizard={() => setWizardOpen(true)}
                    />
                )}

                {wizardOpen && (
                    <FaceEnrollmentWizard
                        employeeId={employee.id}
                        employeeName={fullName}
                        employeeCode={employee.employee_code}
                        onClose={() => setWizardOpen(false)}
                    />
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

function DocumentsSection({
    employeeId,
    documents,
    canManage,
}: {
    employeeId: number;
    documents: DocumentsPayload;
    canManage: boolean;
}) {
    const required = documents.matrix.filter(
        (t) => t.is_required && t.applies_to_employee,
    );
    const uploadedByType = new Map<number, UploadedDocument>();
    documents.uploaded.forEach((d) => {
        if (d.document_type_id !== null) {
            uploadedByType.set(d.document_type_id, d);
        }
    });
    const additional = documents.uploaded.filter(
        (d) => d.document_type_id === null,
    );

    return (
        <Section number="04" label="Documents">
            <div className="space-y-10">
                <div>
                    <h3 className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                        Required matrix
                    </h3>
                    {required.length === 0 ? (
                        <p className="mt-3 text-sm text-yzh-slate">
                            No required documents apply to this employee yet.
                        </p>
                    ) : (
                        <ul className="mt-4 divide-y divide-yzh-bone-soft border-t border-yzh-bone-soft">
                            {required.map((type) => (
                                <RequiredRow
                                    key={type.id}
                                    type={type}
                                    employeeId={employeeId}
                                    uploaded={uploadedByType.get(type.id) ?? null}
                                    canManage={canManage}
                                />
                            ))}
                        </ul>
                    )}
                </div>

                <div>
                    <h3 className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                        Additional documents · {additional.length}
                    </h3>
                    {additional.length === 0 ? (
                        <p className="mt-3 text-sm text-yzh-slate">
                            None uploaded.
                        </p>
                    ) : (
                        <ul className="mt-4 divide-y divide-yzh-bone-soft border-t border-yzh-bone-soft">
                            {additional.map((doc) => (
                                <UploadedRow
                                    key={doc.id}
                                    doc={doc}
                                    employeeId={employeeId}
                                    canManage={canManage}
                                />
                            ))}
                        </ul>
                    )}
                </div>

                {canManage && (
                    <div>
                        <h3 className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                            Upload · {documents.uploaded.length} / {documents.cap}
                        </h3>
                        <UploadForm
                            employeeId={employeeId}
                            matrix={documents.matrix.filter(
                                (t) => t.applies_to_employee,
                            )}
                        />
                    </div>
                )}
            </div>
        </Section>
    );
}

function FaceSection({
    employeeId,
    face,
    canEnroll,
    canReset,
    onLaunchWizard,
}: {
    employeeId: number;
    face: FacePayload;
    canEnroll: boolean;
    canReset: boolean;
    onLaunchWizard: () => void;
}) {
    const status = computeFaceStatus(face);

    return (
        <Section number="06" label="Face verification">
            <div className="space-y-5">
                <div className="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-3">
                    <Field
                        label="Status"
                        value={status.label}
                        mono
                    />
                    <Field
                        label="Last enrolled"
                        value={
                            face.last_enrolled_at
                                ? formatDate(face.last_enrolled_at)
                                : null
                        }
                    />
                    <Field
                        label="Months since"
                        value={
                            face.months_since !== null
                                ? `${face.months_since} / ${face.cadence_months}`
                                : null
                        }
                        mono
                    />
                    <Field
                        label="Failed check-ins"
                        value={`${face.failed_checkin_count}`}
                        mono
                    />
                    <Field
                        label="Cadence"
                        value={`${face.cadence_months} months`}
                        mono
                    />
                </div>
                {(face.requires_reenrollment || status.tone === 'danger') && (
                    <div className="flex items-start gap-3 border border-red-200 bg-red-50 px-4 py-3">
                        <AlertTriangle
                            className="mt-0.5 h-4 w-4 text-red-700"
                            aria-hidden="true"
                        />
                        <div className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-red-700">
                            {face.requires_reenrollment
                                ? 'Re-enrollment required before next check-in.'
                                : 'Past 12-month cadence — re-enroll soon.'}
                        </div>
                    </div>
                )}
                {canEnroll && (
                    <div className="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            onClick={onLaunchWizard}
                            className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink"
                        >
                            <ScanFace className="h-4 w-4" aria-hidden="true" />
                            <span>
                                {face.has_descriptor
                                    ? 'Re-enroll'
                                    : 'Enroll for face verification'}
                            </span>
                            <ArrowUpRight
                                className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                aria-hidden="true"
                            />
                        </button>
                        {canReset && face.has_descriptor && (
                            <button
                                type="button"
                                onClick={() => {
                                    if (
                                        confirm(
                                            'Force re-enrollment? Clears the active descriptor — the employee must re-enroll before their next check-in.',
                                        )
                                    ) {
                                        router.post(
                                            `/employees/${employeeId}/face-enrollment/reset`,
                                            {},
                                            { preserveScroll: true },
                                        );
                                    }
                                }}
                                className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate hover:text-red-700"
                            >
                                Reset enrollment
                            </button>
                        )}
                    </div>
                )}
                <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    {face.photo_count} photos · min quality{' '}
                    {face.min_quality_score.toFixed(2)} · descriptor purges
                    24h after termination (PDPL)
                </p>
            </div>
        </Section>
    );
}

function computeFaceStatus(face: FacePayload): {
    tone: 'ok' | 'soft' | 'warn' | 'danger';
    label: string;
} {
    if (face.requires_reenrollment) {
        return { tone: 'danger', label: 'Forced re-enrollment' };
    }
    if (!face.has_descriptor) {
        return { tone: 'warn', label: 'Never enrolled' };
    }
    if (
        face.months_since !== null &&
        face.months_since >= face.cadence_months
    ) {
        return { tone: 'danger', label: 'Overdue' };
    }
    if (
        face.months_since !== null &&
        face.months_since >= face.cadence_months - 1
    ) {
        return { tone: 'soft', label: 'Due this month' };
    }
    return { tone: 'ok', label: 'Active' };
}

function AssetsSection({ assets }: { assets: AssetsPayload }) {
    const totalValue = assets.current.reduce(
        (sum, asset) => sum + asset.value_piasters,
        0,
    );

    return (
        <Section number="05" label="Assets">
            <div className="space-y-6">
                <div className="flex flex-wrap items-baseline justify-between gap-3">
                    <h3 className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                        Currently held · {assets.current.length}
                    </h3>
                    <span className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate">
                        total {(totalValue / 100).toLocaleString()} EGP
                    </span>
                </div>
                {assets.current.length === 0 ? (
                    <p className="text-sm text-yzh-slate">
                        No assets currently assigned to this employee.
                    </p>
                ) : (
                    <ul className="divide-y divide-yzh-bone-soft border-t border-yzh-bone-soft">
                        {assets.current.map((asset) => (
                            <li
                                key={asset.id}
                                className="grid grid-cols-12 gap-4 py-4 sm:gap-6"
                            >
                                <span className="col-span-3 font-mono text-xs uppercase tracking-[0.18em] text-yzh-text sm:col-span-2">
                                    {asset.category?.name ?? '—'}
                                </span>
                                <Link
                                    href={`/assets/${asset.id}`}
                                    className="col-span-9 text-base font-semibold text-yzh-ink hover:text-yzh-gold sm:col-span-4"
                                >
                                    {asset.name}
                                </Link>
                                <span className="col-span-6 truncate font-mono text-xs text-yzh-slate sm:col-span-3">
                                    {asset.serial_number ?? 'no serial'}
                                </span>
                                <span className="col-span-6 text-right font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text sm:col-span-3">
                                    {(asset.value_piasters / 100).toLocaleString()} EGP
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </Section>
    );
}

function RequiredRow({
    type,
    employeeId,
    uploaded,
    canManage,
}: {
    type: DocumentTypeRow;
    employeeId: number;
    uploaded: UploadedDocument | null;
    canManage: boolean;
}) {
    const status = ! uploaded
        ? { tone: 'warn', label: 'Missing' }
        : uploaded.is_expired
          ? { tone: 'danger', label: 'Expired' }
          : { tone: 'ok', label: 'Verified' };

    const toneClass =
        status.tone === 'danger'
            ? 'text-red-700'
            : status.tone === 'warn'
              ? 'text-yzh-gold'
              : 'text-yzh-text';

    return (
        <li className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:gap-6">
            <div className="flex-1">
                <div className="flex flex-wrap items-baseline gap-3">
                    <span className="text-sm font-medium text-yzh-ink">
                        {type.name}
                    </span>
                    {type.name_ar && (
                        <span className="text-sm text-yzh-slate" dir="rtl">
                            {type.name_ar}
                        </span>
                    )}
                </div>
                {uploaded && (
                    <div className="mt-1 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate">
                        {uploaded.expiry_date
                            ? `Expires ${formatDate(uploaded.expiry_date)}`
                            : 'No expiry'}
                        {uploaded.uploaded_by &&
                            ` · uploaded by ${uploaded.uploaded_by}`}
                    </div>
                )}
            </div>
            <span
                className={`font-mono text-[0.6875rem] uppercase tracking-[0.22em] ${toneClass}`}
            >
                {status.label}
            </span>
            {uploaded && (
                <DocActions
                    employeeId={employeeId}
                    documentId={uploaded.id}
                    canManage={canManage}
                />
            )}
        </li>
    );
}

function UploadedRow({
    doc,
    employeeId,
    canManage,
}: {
    doc: UploadedDocument;
    employeeId: number;
    canManage: boolean;
}) {
    return (
        <li className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:gap-6">
            <div className="flex-1 min-w-0">
                <p className="truncate text-sm font-medium text-yzh-ink">
                    {doc.original_filename}
                </p>
                <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate">
                    {(doc.file_size_bytes / 1024).toFixed(0)} KB · {doc.mime_type}
                    {doc.expiry_date &&
                        ` · expires ${formatDate(doc.expiry_date)}`}
                </p>
            </div>
            <DocActions
                employeeId={employeeId}
                documentId={doc.id}
                canManage={canManage}
            />
        </li>
    );
}

function DocActions({
    employeeId,
    documentId,
    canManage,
}: {
    employeeId: number;
    documentId: number;
    canManage: boolean;
}) {
    return (
        <div className="flex items-center gap-2">
            <a
                href={`/employees/${employeeId}/documents/${documentId}/download`}
                className="inline-flex h-9 w-9 items-center justify-center rounded-md text-yzh-slate transition-colors hover:bg-yzh-bone-soft hover:text-yzh-ink"
                aria-label="Download"
            >
                <Download className="h-4 w-4" aria-hidden="true" />
            </a>
            {canManage && (
                <button
                    type="button"
                    onClick={() => {
                        if (confirm('Delete this document?')) {
                            router.delete(
                                `/employees/${employeeId}/documents/${documentId}`,
                                { preserveScroll: true },
                            );
                        }
                    }}
                    className="inline-flex h-9 w-9 items-center justify-center rounded-md text-yzh-slate transition-colors hover:bg-red-50 hover:text-red-700"
                    aria-label="Delete"
                >
                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                </button>
            )}
        </div>
    );
}

function UploadForm({
    employeeId,
    matrix,
}: {
    employeeId: number;
    matrix: DocumentTypeRow[];
}) {
    const [fileKey, setFileKey] = useState(0);
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
        document_type_id: '' as number | '',
        issued_date: '',
        expiry_date: '',
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(`/employees/${employeeId}/documents`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                reset();
                setFileKey((k) => k + 1);
            },
        });
    };

    return (
        <form
            onSubmit={submit}
            className="mt-4 grid grid-cols-1 gap-x-8 gap-y-5 border-t border-yzh-bone-soft pt-5 sm:grid-cols-2"
        >
            <div className="space-y-2">
                <InputLabel htmlFor="upload_type" value="Document type" />
                <select
                    id="upload_type"
                    value={data.document_type_id}
                    onChange={(e) =>
                        setData(
                            'document_type_id',
                            e.target.value === '' ? '' : Number(e.target.value),
                        )
                    }
                    className="block h-11 w-full rounded-md border border-yzh-bone-soft bg-white px-3 text-sm text-yzh-ink shadow-sm transition-colors focus:border-yzh-gold focus:outline-none focus:ring-2 focus:ring-yzh-gold/30"
                >
                    <option value="">Uncategorized (additional)</option>
                    {matrix.map((t) => (
                        <option key={t.id} value={t.id}>
                            {t.is_required ? '★ ' : ''}
                            {t.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.document_type_id} />
            </div>
            <div className="space-y-2">
                <InputLabel htmlFor="upload_file" value="File (max 10 MB)" />
                <input
                    key={fileKey}
                    id="upload_file"
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                    onChange={(e) =>
                        setData('file', e.target.files?.[0] ?? null)
                    }
                    required
                    className="block w-full text-sm text-yzh-ink file:mr-4 file:rounded file:border-0 file:bg-yzh-bone-soft file:px-3 file:py-2 file:font-mono file:text-[0.6875rem] file:uppercase file:tracking-[0.22em] file:text-yzh-ink hover:file:bg-yzh-gold/15"
                />
                <InputError message={errors.file} />
            </div>
            <div className="space-y-2">
                <InputLabel htmlFor="upload_issued" value="Issued (optional)" />
                <TextInput
                    id="upload_issued"
                    type="date"
                    value={data.issued_date}
                    onChange={(e) => setData('issued_date', e.target.value)}
                />
                <InputError message={errors.issued_date} />
            </div>
            <div className="space-y-2">
                <InputLabel
                    htmlFor="upload_expiry"
                    value="Expiry (optional, auto-fills from type)"
                />
                <TextInput
                    id="upload_expiry"
                    type="date"
                    value={data.expiry_date}
                    onChange={(e) => setData('expiry_date', e.target.value)}
                />
                <InputError message={errors.expiry_date} />
            </div>
            <div className="space-y-2 sm:col-span-2">
                <InputLabel htmlFor="upload_notes" value="Notes (optional)" />
                <TextInput
                    id="upload_notes"
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                />
                <InputError message={errors.notes} />
            </div>
            <div className="sm:col-span-2">
                <button
                    type="submit"
                    disabled={processing || !data.file}
                    className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Uploading' : 'Upload document'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
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
