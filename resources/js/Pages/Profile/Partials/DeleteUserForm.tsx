import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

export default function DeleteUserForm({
    className = '',
}: {
    className?: string;
}) {
    const [confirming, setConfirming] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const closeModal = () => {
        setConfirming(false);
        clearErrors();
        reset();
    };

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();
        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-base font-semibold text-red-700">
                    Delete account
                </h2>
                <p className="mt-1 text-sm text-yzh-slate">
                    Once your account is deleted, all of its data will be
                    permanently removed. Download anything you want to keep
                    first.
                </p>
            </header>

            <div className="mt-6">
                <DangerButton onClick={() => setConfirming(true)}>
                    Delete account
                </DangerButton>
            </div>

            <Modal show={confirming} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6">
                    <h2 className="text-base font-semibold text-yzh-ink">
                        Are you sure?
                    </h2>
                    <p className="mt-2 text-sm text-yzh-slate">
                        This permanently deletes your account and all
                        associated data. Enter your password to confirm.
                    </p>

                    <div className="mt-6 space-y-1.5">
                        <InputLabel
                            htmlFor="password"
                            value="Password"
                            className="sr-only"
                        />
                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            isFocused
                            placeholder="Enter your password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeModal} type="button">
                            Cancel
                        </SecondaryButton>
                        <DangerButton disabled={processing}>
                            {processing ? 'Deleting…' : 'Delete account'}
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
