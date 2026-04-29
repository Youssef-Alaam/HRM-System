import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verify your email" />

            <header className="mb-6">
                <h1 className="text-3xl font-semibold tracking-tight text-yzh-ink">
                    Check your inbox
                </h1>
                <p className="mt-2 text-sm text-yzh-slate">
                    We sent a verification link to the email you registered
                    with. Click it to activate your account.
                </p>
            </header>

            {status === 'verification-link-sent' && (
                <div className="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    A new verification link has been sent to your email.
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <PrimaryButton className="w-full" disabled={processing}>
                    {processing ? 'Sending…' : 'Resend verification email'}
                </PrimaryButton>

                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="block w-full text-center text-sm text-yzh-slate hover:text-yzh-ink hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold rounded"
                >
                    Sign out
                </Link>
            </form>
        </GuestLayout>
    );
}
