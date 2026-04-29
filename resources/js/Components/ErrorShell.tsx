import GuestLayout from '@/Layouts/GuestLayout';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { ReactNode } from 'react';

type CTA = {
    href: string;
    label: string;
};

type Props = {
    title: string;
    code: number;
    label: string;
    heading: string;
    body: ReactNode;
    primary?: CTA;
    secondary?: CTA;
};

export default function ErrorShell({
    title,
    code,
    label,
    heading,
    body,
    primary,
    secondary,
}: Props) {
    return (
        <GuestLayout>
            <Head title={title} />

            <header className="mb-10">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    Error / {code} / {label}
                </p>
                <h1 className="mt-3 text-4xl font-semibold leading-[1.05] tracking-tight text-yzh-ink">
                    {heading}
                </h1>
            </header>

            <div className="text-sm leading-relaxed text-yzh-slate">
                {body}
            </div>

            {(primary || secondary) && (
                <div className="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3">
                    {primary && (
                        <Link
                            href={primary.href}
                            className="group inline-flex min-h-12 items-center justify-center gap-3 border border-yzh-gold px-5 py-3 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-bone"
                        >
                            <span>{primary.label}</span>
                            <ArrowUpRight
                                className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                aria-hidden="true"
                            />
                        </Link>
                    )}
                    {secondary && (
                        <Link
                            href={secondary.href}
                            className="inline-flex min-h-11 items-center rounded font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                        >
                            {secondary.label}
                        </Link>
                    )}
                </div>
            )}
        </GuestLayout>
    );
}

/**
 * Returns the route a logged-in user goes to ('dashboard') or the public route
 * for a guest ('login'). Used by error pages so the primary CTA always lands
 * the user somewhere they can act.
 */
export function useHomeRoute(): { href: string; label: string } {
    const auth = usePage<PageProps>().props.auth;
    if (auth?.user) {
        return { href: route('dashboard'), label: 'Back to dashboard' };
    }
    return { href: route('login'), label: 'Sign in' };
}
