import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="grid min-h-screen lg:grid-cols-[5fr_6fr]">
            {/* Left rail — brand panel (desktop only).
                Carries the shared view-transition-name "brand-panel" so the
                full Welcome canvas morphs INTO this aside when the user clicks
                Sign in. The huge gold YZH wordmark in the middle row carries
                "yzh-hero" — the gold word from Welcome's headline lands there. */}
            <aside
                className="vt-brand-panel relative hidden overflow-hidden bg-yzh-ink p-12 text-yzh-bone lg:flex lg:flex-col lg:justify-between"
                style={{
                    backgroundImage:
                        'linear-gradient(to right, rgba(208,169,70,0.04) 1px, transparent 1px), linear-gradient(to bottom, rgba(208,169,70,0.04) 1px, transparent 1px)',
                    backgroundSize: '60px 60px',
                }}
            >
                {/* Top — title-block strip */}
                <div className="relative">
                    <Link
                        href="/"
                        className="inline-block rounded outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                    >
                        <img
                            src="/images/yzh-mark.png"
                            alt="YZH Solutions"
                            className="h-9 w-auto"
                        />
                    </Link>
                    <div className="mt-6 flex flex-wrap gap-x-6 gap-y-1 border-t border-yzh-ink-mute pt-3 font-mono text-[0.625rem] uppercase tracking-[0.24em]">
                        <span>
                            <span className="text-yzh-text">Project</span>
                            <span className="ml-2 text-yzh-bone-soft">
                                HR-001
                            </span>
                        </span>
                        <span>
                            <span className="text-yzh-text">Section</span>
                            <span className="ml-2 text-yzh-bone-soft">
                                A.02
                            </span>
                        </span>
                    </div>
                </div>

                {/* Middle — the morph landing.
                    The huge gold YZH lands here from Welcome's headline. */}
                <div className="relative space-y-6">
                    <span className="vt-yzh-hero inline-block text-7xl font-semibold leading-[0.85] tracking-tight text-yzh-gold xl:text-8xl">
                        YZH
                    </span>
                    <p className="max-w-md text-sm leading-relaxed text-yzh-bone-soft">
                        For YZH Solutions employees only. Sign in with the
                        credentials provided by HR.
                    </p>
                </div>

                {/* Bottom — title-block footer */}
                <p className="relative font-mono text-[0.625rem] uppercase tracking-[0.24em] text-yzh-text">
                    YZH Solutions / Cairo / Internal use only
                </p>
            </aside>

            {/* Right side — form */}
            <main className="flex flex-col items-center justify-center bg-yzh-bone px-6 py-12 lg:px-16">
                {/* Mobile header */}
                <Link href="/" className="mb-10 lg:hidden">
                    <img
                        src="/images/yzh-mark.png"
                        alt="YZH Solutions"
                        className="h-8 w-auto"
                    />
                </Link>

                <div className="w-full max-w-md">{children}</div>
            </main>
        </div>
    );
}
