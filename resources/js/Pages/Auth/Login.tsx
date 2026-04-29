import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PasswordInput from '@/Components/PasswordInput';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Sign in" />

            <header className="mb-10">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    A.02 / Sign in
                </p>
                <h1 className="mt-3 text-4xl font-semibold leading-[1.05] tracking-tight text-yzh-ink">
                    Sign in.
                </h1>
            </header>

            {status && (
                <div className="mb-6 border-l-0 border-t border-green-200 bg-green-50 px-4 py-3 font-mono text-xs uppercase tracking-[0.18em] text-green-700">
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
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="you@yzh.solutions"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <InputLabel htmlFor="password" value="Password" />
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="rounded font-mono text-[0.6875rem] uppercase tracking-[0.2em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                            >
                                Forgot?
                            </Link>
                        )}
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        value={data.password}
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password} />
                </div>

                <label className="flex items-center gap-3 text-sm text-yzh-slate">
                    <Checkbox
                        name="remember"
                        checked={data.remember}
                        onChange={(e) =>
                            setData(
                                'remember',
                                (e.target.checked || false) as false,
                            )
                        }
                    />
                    Keep me signed in on this device
                </label>

                <button
                    type="submit"
                    disabled={processing}
                    className="group inline-flex min-h-12 w-full items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Signing in' : 'Sign in'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </form>

            <footer className="mt-12 border-t border-yzh-bone-soft pt-6">
                <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                    Need an account? Contact your HR admin.
                </p>
            </footer>
        </GuestLayout>
    );
}
