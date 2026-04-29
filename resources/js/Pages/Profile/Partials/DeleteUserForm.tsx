import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PasswordInput from '@/Components/PasswordInput';
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
            <h2 className="text-xl font-semibold tracking-tight text-red-700">
                Delete account.
            </h2>
            <p className="mt-2 max-w-md text-sm leading-relaxed text-yzh-slate">
                Once your account is deleted, all of its data will be removed.
                Download anything you want to keep first.
            </p>

            <div className="mt-8">
                <button
                    type="button"
                    onClick={() => setConfirming(true)}
                    className="group inline-flex min-h-12 items-center justify-center gap-3 border border-red-500 px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-red-700 transition-colors duration-150 ease-out hover:bg-red-500 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone"
                >
                    Delete account
                </button>
            </div>

            <Modal show={confirming} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-6 sm:p-8">
                    <p className="font-mono text-xs uppercase tracking-[0.24em] text-red-700">
                        Confirm
                    </p>
                    <h2 className="mt-3 text-2xl font-semibold tracking-tight text-yzh-ink">
                        Delete your account.
                    </h2>
                    <p className="mt-3 text-sm leading-relaxed text-yzh-slate">
                        This permanently deletes your account and all
                        associated data. Enter your password to confirm.
                    </p>

                    <div className="mt-6 space-y-2">
                        <InputLabel
                            htmlFor="password"
                            value="Password"
                            className="sr-only"
                        />
                        <PasswordInput
                            id="password"
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

                    <div className="mt-8 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            onClick={closeModal}
                            className="inline-flex min-h-11 items-center justify-center px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold rounded"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex min-h-11 items-center justify-center gap-3 border border-red-500 px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-red-700 transition-colors duration-150 ease-out hover:bg-red-500 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {processing ? 'Deleting' : 'Delete account'}
                        </button>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
