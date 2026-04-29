import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="grid min-h-screen lg:grid-cols-[5fr_6fr]">
            {/* Left rail — brand panel (desktop only) */}
            <aside className="relative hidden lg:flex lg:flex-col lg:justify-between bg-yzh-ink p-12 text-yzh-bone">
                <Link
                    href="/"
                    className="flex items-baseline gap-2 outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold rounded"
                >
                    <img
                        src="/images/yzh-mark.png"
                        alt="YZH"
                        className="h-9 w-auto"
                    />
                    <span className="text-sm font-medium tracking-[0.3em] text-yzh-bone-soft">
                        HR
                    </span>
                </Link>

                <div className="space-y-6">
                    <div className="inline-flex items-center gap-2 rounded-full border border-yzh-ink-mute px-3 py-1 text-xs uppercase tracking-widest text-yzh-gold">
                        <span className="h-1.5 w-1.5 rounded-full bg-yzh-gold" />
                        Internal HR system
                    </div>
                    <h2 className="max-w-md text-3xl font-semibold leading-tight tracking-tight">
                        People, attendance, and payroll —{' '}
                        <span className="text-yzh-gold">in one place</span>.
                    </h2>
                    <p className="max-w-md text-sm text-yzh-bone-soft">
                        For YZH Solutions employees only. Sign in with the
                        credentials provided by HR.
                    </p>
                </div>

                <p className="text-xs text-yzh-text">
                    YZH Solutions · Internal use only
                </p>
            </aside>

            {/* Right side — form */}
            <main className="flex flex-col items-center justify-center bg-yzh-bone px-6 py-12 lg:px-16">
                {/* Mobile header */}
                <Link
                    href="/"
                    className="mb-10 flex items-baseline gap-2 lg:hidden"
                >
                    <img
                        src="/images/yzh-mark.png"
                        alt="YZH"
                        className="h-8 w-auto"
                    />
                    <span className="text-sm font-medium tracking-[0.3em] text-yzh-slate">
                        HR
                    </span>
                </Link>

                <div className="w-full max-w-md">{children}</div>
            </main>
        </div>
    );
}
