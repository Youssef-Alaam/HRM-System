import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Register" />

            <header className="mb-8">
                <h1 className="text-3xl font-semibold tracking-tight text-yzh-ink">
                    Create your account
                </h1>
                <p className="mt-2 text-sm text-yzh-slate">
                    For YZH Solutions employees. Use your work email.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-1.5">
                    <InputLabel htmlFor="name" value="Full name" />
                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Ahmed El-Sayed"
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="space-y-1.5">
                    <InputLabel htmlFor="email" value="Work email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="you@yzh.solutions"
                        required
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="space-y-1.5">
                    <InputLabel htmlFor="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="At least 8 characters"
                        required
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="space-y-1.5">
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Confirm password"
                    />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        placeholder="Repeat your password"
                        required
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <PrimaryButton
                    className="w-full"
                    disabled={processing}
                >
                    {processing ? 'Creating account…' : 'Create account'}
                </PrimaryButton>
            </form>

            <p className="mt-8 text-center text-sm text-yzh-slate">
                Already have an account?{' '}
                <Link
                    href={route('login')}
                    className="font-medium text-yzh-gold-600 hover:text-yzh-gold-700 hover:underline"
                >
                    Sign in
                </Link>
            </p>
        </GuestLayout>
    );
}
