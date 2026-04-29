import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <>
            <Head title="Profile" />

            <div className="space-y-14 sm:space-y-20">
                <Section section="00" label="Identity">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </Section>

                <Section section="01" label="Password">
                    <UpdatePasswordForm className="max-w-xl" />
                </Section>

                <Section section="02" label="Danger zone" tone="danger">
                    <DeleteUserForm className="max-w-xl" />
                </Section>
            </div>
        </>
    );
}

function Section({
    section,
    label,
    tone = 'default',
    children,
}: {
    section: string;
    label: string;
    tone?: 'default' | 'danger';
    children: ReactNode;
}) {
    const ruleClass =
        tone === 'danger'
            ? 'border-t border-red-200'
            : 'border-t border-yzh-bone-soft';
    const sectionLabelClass =
        tone === 'danger' ? 'text-red-700' : 'text-yzh-gold';

    return (
        <section>
            <div className={`${ruleClass} pt-5`}>
                <div className="flex items-baseline gap-3">
                    <span
                        className={`font-mono text-xs uppercase tracking-[0.24em] ${sectionLabelClass}`}
                    >
                        {section}
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

Edit.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    P.01 / Account
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Profile.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Edit;
