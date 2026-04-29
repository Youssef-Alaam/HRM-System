import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PasswordInput from '@/Components/PasswordInput';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

export default function UpdatePasswordForm({
    className = '',
}: {
    className?: string;
}) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }
                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section className={className}>
            <h2 className="text-xl font-semibold tracking-tight text-yzh-ink">
                Change password.
            </h2>
            <p className="mt-2 text-sm leading-relaxed text-yzh-slate">
                Use a long password you don't reuse anywhere else.
            </p>

            <form onSubmit={updatePassword} className="mt-8 space-y-6">
                <div className="space-y-2">
                    <InputLabel
                        htmlFor="current_password"
                        value="Current password"
                    />
                    <PasswordInput
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        onChange={(e) =>
                            setData('current_password', e.target.value)
                        }
                        autoComplete="current-password"
                    />
                    <InputError message={errors.current_password} />
                </div>

                <div className="space-y-2">
                    <InputLabel htmlFor="password" value="New password" />
                    <PasswordInput
                        id="password"
                        ref={passwordInput}
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="new-password"
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
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <div className="flex items-center gap-5">
                    <button
                        type="submit"
                        disabled={processing}
                        className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span>{processing ? 'Saving' : 'Update password'}</span>
                        <ArrowUpRight
                            className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                            aria-hidden="true"
                        />
                    </button>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-out duration-150"
                        enterFrom="opacity-0"
                        leave="transition ease-in duration-150"
                        leaveTo="opacity-0"
                    >
                        <p className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                            Updated
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
