import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PasswordInput from '@/Components/PasswordInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
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

            <header className="mb-10">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    A.05 / Verify
                </p>
                <h1 className="mt-3 text-4xl font-semibold leading-[1.05] tracking-tight text-yzh-ink">
                    Confirm your password.
                </h1>
                <p className="mt-4 max-w-sm text-sm leading-relaxed text-yzh-slate">
                    For your security, confirm your password to continue.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-6">
                <div className="space-y-2">
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

                <button
                    type="submit"
                    disabled={processing}
                    className="group inline-flex min-h-12 w-full items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span>{processing ? 'Confirming' : 'Confirm'}</span>
                    <ArrowUpRight
                        className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </button>
            </form>
        </GuestLayout>
    );
}
