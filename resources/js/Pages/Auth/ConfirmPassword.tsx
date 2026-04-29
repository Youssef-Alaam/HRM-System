import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PasswordInput from '@/Components/PasswordInput';
import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Confirm password" />

            <header className="mb-8">
                <h1 className="text-3xl font-semibold tracking-tight text-yzh-ink">
                    Confirm your password
                </h1>
                <p className="mt-2 text-sm text-yzh-slate">
                    For your security, please confirm your password to continue.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-1.5">
                    <InputLabel htmlFor="password" value="Password" />
                    <PasswordInput
                        id="password"
                        name="password"
                        value={data.password}
                        isFocused={true}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password} />
                </div>

                <PrimaryButton className="w-full" disabled={processing}>
                    {processing ? 'Confirming…' : 'Confirm'}
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
