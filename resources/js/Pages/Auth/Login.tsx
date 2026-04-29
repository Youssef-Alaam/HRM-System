import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
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
            <Head title="Log in" />

            <header className="mb-8">
                <h1 className="text-3xl font-semibold tracking-tight text-yzh-ink">
                    Welcome back
                </h1>
                <p className="mt-2 text-sm text-yzh-slate">
                    Sign in to continue to YZH HR.
                </p>
            </header>

            {status && (
                <div className="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-1.5">
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

                <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                        <InputLabel htmlFor="password" value="Password" />
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="rounded text-xs font-medium text-yzh-gold-600 hover:text-yzh-gold-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                            >
                                Forgot password?
                            </Link>
                        )}
                    </div>
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password} />
                </div>

                <label className="flex items-center gap-2 text-sm text-yzh-slate">
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

                <PrimaryButton
                    className="w-full"
                    disabled={processing}
                >
                    {processing ? 'Signing in…' : 'Sign in'}
                </PrimaryButton>
            </form>

            <p className="mt-8 text-center text-sm text-yzh-slate">
                Don't have an account?{' '}
                <Link
                    href={route('register')}
                    className="font-medium text-yzh-gold-600 hover:text-yzh-gold-700 hover:underline"
                >
                    Register here
                </Link>
            </p>
        </GuestLayout>
    );
}
