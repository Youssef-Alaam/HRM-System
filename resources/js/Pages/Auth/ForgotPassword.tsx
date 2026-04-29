import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Forgot password" />

            <header className="mb-10">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    A.03 / Password reset
                </p>
                <h1 className="mt-3 text-4xl font-semibold leading-[1.05] tracking-tight text-yzh-ink">
                    Forgot your password?
                </h1>
                <p className="mt-4 max-w-sm text-sm leading-relaxed text-yzh-slate">
                    Enter your work email and we'll send a reset link.
                </p>
            </header>

            {status && (
                <div className="mb-6 border-t border-green-200 bg-green-50 px-4 py-3 font-mono text-xs uppercase tracking-[0.18em] text-green-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-2">
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="you@yzh.solutions"
                    />
                    <InputError message={errors.email} />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="group inline-flex min-h-12 w-full items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Sending' : 'Send reset link'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </form>

            <footer className="mt-12 border-t border-yzh-bone-soft pt-6">
                <Link
                    href={route('login')}
                    className="inline-flex min-h-11 items-center gap-2 rounded font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                >
                    <ArrowLeft className="h-3.5 w-3.5" aria-hidden="true" />
                    Back to sign in
                </Link>
            </footer>
        </GuestLayout>
    );
}
