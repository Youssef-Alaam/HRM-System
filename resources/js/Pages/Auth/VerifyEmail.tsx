import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
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

            <header className="mb-10">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    A.06 / Email verification
                </p>
                <h1 className="mt-3 text-4xl font-semibold leading-[1.05] tracking-tight text-yzh-ink">
                    Check your inbox.
                </h1>
                <p className="mt-4 max-w-sm text-sm leading-relaxed text-yzh-slate">
                    We sent a verification link to the email you registered
                    with. Click it to activate your account.
                </p>
            </header>

            {status === 'verification-link-sent' && (
                <div className="mb-6 border-t border-green-200 bg-green-50 px-4 py-3 font-mono text-xs uppercase tracking-[0.18em] text-green-700">
                    A new verification link has been sent.
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <button
                    type="submit"
                    disabled={processing}
                    className="group inline-flex min-h-12 w-full items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>
                        {processing ? 'Sending' : 'Resend verification email'}
                    </span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </form>

            <footer className="mt-12 border-t border-yzh-bone-soft pt-6">
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="rounded font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                >
                    Sign out
                </Link>
            </footer>
        </GuestLayout>
    );
}
