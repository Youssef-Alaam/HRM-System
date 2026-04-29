import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { MouseEvent } from 'react';

type DocumentWithViewTransition = Document & {
    startViewTransition?: (callback: () => void | Promise<void>) => unknown;
};

/**
 * Welcome — "Architectural drawing" composition.
 *
 * The whole page is framed as a real engineering drawing:
 *   • Top + bottom strips are title blocks with project metadata
 *     (PROJECT / CLIENT / ISSUED / REV / SCALE / DRAWING)
 *   • Hero is the drawing content, anchored on the right of an asymmetric
 *     12-col grid; left rail is a scale ruler (decorative SVG, aria-hidden)
 *   • Background carries a faint 60px gold grid (3% opacity) like draftsman
 *     paper — gives the page texture without photography
 *   • Gold accent is reserved for one word in the headline + the kicker
 *     index marker; nothing else
 */
export default function Welcome({ auth }: PageProps) {
    const signedIn = Boolean(auth.user);
    const ctaHref = signedIn ? route('dashboard') : route('login');
    const ctaLabel = signedIn ? 'Open dashboard' : 'Sign in';
    const issuedYear = new Date().getFullYear();

    /**
     * Wrap the Inertia visit in document.startViewTransition() so the
     * dark canvas morphs into GuestLayout's left aside (shared
     * view-transition-name="brand-panel"). Falls back transparently on
     * browsers without the API.
     */
    const handleCtaClick = (event: MouseEvent<Element>) => {
        if (typeof document === 'undefined') return;
        const doc = document as DocumentWithViewTransition;
        if (typeof doc.startViewTransition !== 'function') return;
        event.preventDefault();
        doc.startViewTransition(
            () =>
                new Promise<void>((resolve) => {
                    router.visit(ctaHref, {
                        onFinish: () => resolve(),
                    });
                }),
        );
    };

    return (
        <>
            <Head title="YZH HR" />

            <div
                className="vt-brand-panel relative flex min-h-screen flex-col bg-yzh-ink text-yzh-bone selection:bg-yzh-gold selection:text-yzh-ink"
                style={{
                    backgroundImage:
                        'linear-gradient(to right, rgba(208,169,70,0.04) 1px, transparent 1px), linear-gradient(to bottom, rgba(208,169,70,0.04) 1px, transparent 1px)',
                    backgroundSize: '60px 60px',
                }}
            >
                {/* TITLE BLOCK — TOP */}
                <header className="border-b border-yzh-ink-mute px-6 py-4 sm:px-10 lg:px-16">
                    <div className="flex flex-wrap items-center justify-between gap-x-8 gap-y-3">
                        <Link
                            href="/"
                            className="rounded outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                        >
                            <img
                                src="/images/yzh-mark.png"
                                alt="YZH Solutions"
                                className="h-8 w-auto"
                            />
                        </Link>

                        {/* Drawing metadata — desktop only; collapses on mobile */}
                        <dl className="hidden items-center gap-x-10 gap-y-1 md:flex">
                            <Field label="Project" value="HR-001" />
                            <Field label="Client" value="YZH Solutions" />
                            <Field label="Issued" value={String(issuedYear)} />
                            <Field label="Rev" value="A" />
                        </dl>

                        {/* Stamp-style CTA — bordered, not a pill */}
                        <Link
                            href={ctaHref}
                            onClick={handleCtaClick}
                            className="group inline-flex min-h-11 items-center gap-3 border border-yzh-gold px-4 py-2 font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold transition-colors duration-150 ease-out hover:bg-yzh-gold hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold focus-visible:ring-offset-2 focus-visible:ring-offset-yzh-ink"
                        >
                            <span>{ctaLabel}</span>
                            <ArrowUpRight
                                className="h-4 w-4 transition-transform duration-150 ease-out group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                aria-hidden="true"
                            />
                        </Link>
                    </div>
                </header>

                {/* DRAWING CONTENT */}
                <main className="flex flex-1 items-center px-6 py-16 sm:px-10 sm:py-20 lg:px-16 lg:py-24">
                    <div className="grid w-full grid-cols-12 gap-x-4 gap-y-12 sm:gap-x-6">
                        {/* Left rail — scale ruler (decorative) */}
                        <aside
                            className="col-span-12 hidden md:col-span-2 md:block lg:col-span-1"
                            aria-hidden="true"
                        >
                            <ScaleRuler />
                        </aside>

                        {/* Hero */}
                        <div className="col-span-12 md:col-span-10 lg:col-span-11">
                            <p className="mb-8 font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold sm:mb-10">
                                <span className="mr-3 text-yzh-text">A.01</span>
                                Welcome
                            </p>

                            <h1 className="text-[2.5rem] font-semibold leading-[1.05] tracking-tight text-yzh-bone sm:text-6xl lg:text-8xl xl:text-9xl">
                                For the people
                                <br />
                                who build{' '}
                                <span className="vt-yzh-hero inline-block text-yzh-gold">
                                    YZH
                                </span>
                                .
                            </h1>

                            <p className="mt-10 max-w-xl text-base leading-relaxed text-yzh-bone-soft sm:mt-14 sm:text-lg">
                                One internal system for time, leave, payroll,
                                people, and audit. Built in Cairo for the way
                                YZH Solutions actually works.
                            </p>

                            <p className="mt-10 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                                Modules{' '}
                                <span className="mx-3 text-yzh-ink-mute">/</span>
                                <span className="text-yzh-bone-soft">
                                    Attendance · Leave · Payroll · Org · Audit
                                </span>
                            </p>
                        </div>
                    </div>
                </main>

                {/* TITLE BLOCK — BOTTOM */}
                <footer className="border-t border-yzh-ink-mute px-6 py-4 sm:px-10 lg:px-16">
                    <div className="flex flex-wrap items-center justify-between gap-x-8 gap-y-2 font-mono text-[0.625rem] uppercase tracking-[0.24em] text-yzh-text">
                        <span>Scale 1:1</span>
                        <span className="hidden sm:inline">
                            Drawing 001 of 001
                        </span>
                        <span>YZH Solutions / Cairo</span>
                        <span className="hidden md:inline">
                            Internal use only
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-baseline gap-2">
            <dt className="font-mono text-[0.625rem] uppercase tracking-[0.24em] text-yzh-text">
                {label}
            </dt>
            <dd className="font-mono text-xs uppercase tracking-[0.18em] text-yzh-bone-soft">
                {value}
            </dd>
        </div>
    );
}

/**
 * Vertical scale ruler — purely decorative, evokes a draftsman's edge ruler.
 * Tick marks every 20px, numbered every 40px in monospace below the tick.
 */
function ScaleRuler() {
    const ticks = Array.from({ length: 11 });
    return (
        <svg
            viewBox="0 0 40 280"
            className="h-64 w-10 text-yzh-ink-mute"
            fill="none"
            stroke="currentColor"
            strokeWidth="1"
        >
            {/* Vertical line */}
            <line x1="20" y1="0" x2="20" y2="280" />
            {ticks.map((_, i) => {
                const y = 14 + i * 25;
                const isMajor = i % 2 === 0;
                return (
                    <g key={i}>
                        <line
                            x1={isMajor ? 12 : 16}
                            y1={y}
                            x2={isMajor ? 28 : 24}
                            y2={y}
                        />
                        {isMajor && (
                            <text
                                x="6"
                                y={y + 3}
                                fontSize="7"
                                fontFamily="ui-monospace, monospace"
                                fill="currentColor"
                                textAnchor="end"
                            >
                                {String(i / 2).padStart(2, '0')}
                            </text>
                        )}
                    </g>
                );
            })}
        </svg>
    );
}
