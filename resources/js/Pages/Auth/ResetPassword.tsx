import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PasswordInput from '@/Components/PasswordInput';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
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

            <header className="mb-10">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    A.04 / New password
                </p>
                <h1 className="mt-3 text-4xl font-semibold leading-[1.05] tracking-tight text-yzh-ink">
                    Set a new password.
                </h1>
                <p className="mt-4 max-w-sm text-sm leading-relaxed text-yzh-slate">
                    Choose a strong password. At least 8 characters.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-2">
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

                <div className="space-y-2">
                    <InputLabel htmlFor="password" value="New password" />
                    <PasswordInput
                        id="password"
                        name="password"
                        value={data.password}
                        autoComplete="new-password"
                        isFocused={true}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="At least 8 characters"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="space-y-2">
                    <InputLabel
                        htmlFor="password_confirmation"
                        value="Confirm new password"
                    />
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="group inline-flex min-h-12 w-full items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Resetting' : 'Reset password'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </form>
        </GuestLayout>
    );
}
