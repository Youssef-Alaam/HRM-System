import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }: PageProps) {
    return (
        <>
            <Head title="YZH HR" />
            <div className="min-h-screen bg-yzh-ink text-yzh-bone selection:bg-yzh-gold selection:text-yzh-ink">
                {/* Top bar */}
                <header className="flex items-center justify-between px-6 py-5 lg:px-12">
                    <div className="flex items-baseline gap-2">
                        <img
                            src="/images/yzh-mark.png"
                            alt="YZH"
                            className="h-8 w-auto"
                        />
                        <span className="text-sm font-medium tracking-[0.3em] text-yzh-bone-soft">
                            HR
                        </span>
                    </div>
                    <nav className="flex items-center gap-4 text-sm">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-md bg-yzh-gold px-4 py-2 font-medium text-yzh-ink transition hover:bg-yzh-gold-400"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="rounded-md px-4 py-2 text-yzh-bone-soft transition hover:text-yzh-gold"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-md bg-yzh-gold px-4 py-2 font-medium text-yzh-ink transition hover:bg-yzh-gold-400"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                {/* Hero */}
                <main className="mx-auto flex max-w-6xl flex-col items-start gap-10 px-6 pt-20 pb-32 lg:px-12 lg:pt-32">
                    <div className="inline-flex items-center gap-2 rounded-full border border-yzh-ink-mute bg-yzh-ink-soft px-3 py-1 text-xs uppercase tracking-widest text-yzh-gold">
                        <span className="h-1.5 w-1.5 rounded-full bg-yzh-gold" />
                        Internal HR system
                    </div>

                    <h1 className="max-w-3xl text-5xl font-semibold leading-tight tracking-tight lg:text-6xl">
                        People, attendance, and payroll —{' '}
                        <span className="text-yzh-gold">in one place</span>.
                    </h1>

                    <p className="max-w-2xl text-lg text-yzh-bone-soft">
                        YZH HR replaces Mawared with a system built for the way
                        we actually work. Faster check-ins, clearer leave
                        approvals, fewer surprises on payday.
                    </p>

                    <div className="flex flex-wrap gap-3 pt-2">
                        {!auth.user && (
                            <Link
                                href={route('register')}
                                className="rounded-md bg-yzh-gold px-6 py-3 font-medium text-yzh-ink transition hover:bg-yzh-gold-400"
                            >
                                Get started
                            </Link>
                        )}
                        <a
                            href="#features"
                            className="rounded-md border border-yzh-ink-mute px-6 py-3 text-yzh-bone transition hover:border-yzh-gold hover:text-yzh-gold"
                        >
                            What's inside
                        </a>
                    </div>
                </main>

                {/* Feature cards */}
                <section
                    id="features"
                    className="mx-auto grid max-w-6xl gap-4 px-6 pb-24 sm:grid-cols-2 lg:grid-cols-3 lg:px-12"
                >
                    {[
                        {
                            title: 'Attendance',
                            body: 'GPS + selfie check-in, verdict-based face verification, late detection per workweek.',
                        },
                        {
                            title: 'Leave',
                            body: 'Egyptian-law-compliant balances, approval cascade, weekend confirmation, public holiday handling.',
                        },
                        {
                            title: 'Payroll',
                            body: 'Tax + SI + health insurance, expat handling, EOSB, Form 6 generation.',
                        },
                        {
                            title: 'Org chart',
                            body: 'Department hierarchy, reports tree, manager delegation periods.',
                        },
                        {
                            title: 'Audit log',
                            body: 'Every write captured with user, IP, before/after diff. Soft delete only.',
                        },
                        {
                            title: 'Multi-tenant ready',
                            body: 'Architected for SaaS from day one. org_id on every business table.',
                        },
                    ].map((f) => (
                        <div
                            key={f.title}
                            className="rounded-lg border border-yzh-ink-mute bg-yzh-ink-soft p-5 transition hover:border-yzh-gold/50"
                        >
                            <h3 className="text-base font-semibold text-yzh-bone">
                                {f.title}
                            </h3>
                            <p className="mt-2 text-sm text-yzh-bone-soft">
                                {f.body}
                            </p>
                        </div>
                    ))}
                </section>

                <footer className="border-t border-yzh-ink-mute px-6 py-6 text-center text-xs text-yzh-text lg:px-12">
                    YZH Solutions · HR · Internal use only
                </footer>
            </div>
        </>
    );
}
