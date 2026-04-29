import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Reset password" />

            <header className="mb-8">
                <h1 className="text-3xl font-semibold tracking-tight text-yzh-ink">
                    Set a new password
                </h1>
                <p className="mt-2 text-sm text-yzh-slate">
                    Choose a strong password — at least 8 characters.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-1.5">
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="space-y-1.5">
                    <InputLabel htmlFor="password" value="New password" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="new-password"
                        isFocused={true}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="At least 8 characters"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="space-y-1.5">
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Confirm new password"
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
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <PrimaryButton className="w-full" disabled={processing}>
                    {processing ? 'Resetting…' : 'Reset password'}
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
