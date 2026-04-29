import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
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

            <header className="mb-8">
                <h1 className="text-3xl font-semibold tracking-tight text-yzh-ink">
                    Reset your password
                </h1>
                <p className="mt-2 text-sm text-yzh-slate">
                    Enter your work email and we'll send a reset link.
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
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="you@yzh.solutions"
                    />
                    <InputError message={errors.email} />
                </div>

                <PrimaryButton className="w-full" disabled={processing}>
                    {processing ? 'Sending…' : 'Send reset link'}
                </PrimaryButton>
            </form>

            <p className="mt-8 text-center text-sm text-yzh-slate">
                <Link
                    href={route('login')}
                    className="font-medium text-yzh-gold-600 hover:text-yzh-gold-700 hover:underline"
                >
                    ← Back to sign in
                </Link>
            </p>
        </GuestLayout>
    );
}
