import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-1">
                    <p className="text-xs uppercase tracking-widest text-yzh-text">
                        Account
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight text-yzh-ink">
                        Profile
                    </h1>
                </div>
            }
        >
            <Head title="Profile" />

            <div className="space-y-6">
                <section className="rounded-lg border border-yzh-bone-soft bg-white p-6 shadow-sm sm:p-8">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </section>

                <section className="rounded-lg border border-yzh-bone-soft bg-white p-6 shadow-sm sm:p-8">
                    <UpdatePasswordForm className="max-w-xl" />
                </section>

                <section className="rounded-lg border border-red-200 bg-red-50/40 p-6 shadow-sm sm:p-8">
                    <DeleteUserForm className="max-w-xl" />
                </section>
            </div>
        </AppLayout>
    );
}
