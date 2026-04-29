import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
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
            <header>
                <h2 className="text-base font-semibold text-yzh-ink">
                    Change password
                </h2>
                <p className="mt-1 text-sm text-yzh-slate">
                    Use a long password you don't reuse anywhere else.
                </p>
            </header>

            <form onSubmit={updatePassword} className="mt-6 space-y-5">
                <div className="space-y-1.5">
                    <InputLabel
                        htmlFor="current_password"
                        value="Current password"
                    />
                    <TextInput
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        onChange={(e) =>
                            setData('current_password', e.target.value)
                        }
                        type="password"
                        autoComplete="current-password"
                    />
                    <InputError message={errors.current_password} />
                </div>

                <div className="space-y-1.5">
                    <InputLabel htmlFor="password" value="New password" />
                    <TextInput
                        id="password"
                        ref={passwordInput}
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        type="password"
                        autoComplete="new-password"
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
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        type="password"
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>
                        {processing ? 'Saving…' : 'Update password'}
                    </PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-out duration-150"
                        enterFrom="opacity-0"
                        leave="transition ease-in duration-150"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-yzh-slate">Updated.</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
