import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}: {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <h2 className="text-xl font-semibold tracking-tight text-yzh-ink">
                Identity.
            </h2>
            <p className="mt-2 text-sm leading-relaxed text-yzh-slate">
                Your name and email as they appear across YZH HR.
            </p>

            <form onSubmit={submit} className="mt-8 space-y-6">
                <div className="space-y-2">
                    <InputLabel htmlFor="name" value="Full name" />
                    <TextInput
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="space-y-2">
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                    />
                    <InputError message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div className="border-t border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <span>Your email address is unverified. </span>
                        <Link
                            href={route('verification.send')}
                            method="post"
                            as="button"
                            className="font-medium underline underline-offset-2 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold rounded"
                        >
                            Resend verification email
                        </Link>
                        {status === 'verification-link-sent' && (
                            <p className="mt-2 font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-green-700">
                                A new verification link has been sent.
                            </p>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-5">
                    <button
                        type="submit"
                        disabled={processing}
                        className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span>{processing ? 'Saving' : 'Save changes'}</span>
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
                            Saved
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
