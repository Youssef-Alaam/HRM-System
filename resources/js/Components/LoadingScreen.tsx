import { CSSProperties } from 'react';

/**
 * LoadingScreen — the in-app navigation loading state.
 *
 * Wired into AppLayout via Inertia router events. Fires after a 200ms debounce
 * on `router.on('start')` so fast loads don't flicker; clears on
 * `router.on('finish')`. Renders inside AppLayout's main content area while the
 * AppLayout's top header simultaneously swaps to the destination's title.
 *
 * Visual: a 90° gold arc strikes from one anchor to another (compass radius
 * being drawn), title fades up below, gold dot pulses next to "Loading".
 */

type Props = {
    title: string;
    section?: string;
};

const fadeUp = (delayMs: number, durationMs = 320): CSSProperties => ({
    animation: `ls-fade-up ${durationMs}ms cubic-bezier(0.16, 1, 0.3, 1) ${delayMs}ms both`,
});

const fadeIn = (delayMs: number, durationMs = 320): CSSProperties => ({
    animation: `ls-fade-in ${durationMs}ms ease-out ${delayMs}ms both`,
});

const dotPulse: CSSProperties = {
    animation: 'ls-dot-pulse 1.6s ease-in-out infinite',
};

export default function LoadingScreen({ title, section }: Props) {
    // Quarter-circle from (10, 100) up-right to (100, 10), radius 90 ≈ 126px arc.
    const arcLength = 126;

    return (
        <div className="flex min-h-[60vh] items-center justify-center px-6">
            <div className="flex w-full max-w-xl flex-col items-start">
                <svg
                    viewBox="0 0 110 110"
                    className="h-24 w-24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.25"
                    aria-hidden="true"
                >
                    {/* Light reference cross — fades in first */}
                    <g
                        className="text-yzh-ink-mute"
                        stroke="currentColor"
                        style={{
                            opacity: 0,
                            animation: 'ls-fade-in 240ms ease-out 100ms both',
                        }}
                    >
                        <line x1="10" y1="100" x2="100" y2="100" />
                        <line x1="10" y1="100" x2="10" y2="10" />
                    </g>
                    {/* The arc — strikes from (10,100) up-right to (100,10) */}
                    <path
                        d="M 10 100 A 90 90 0 0 1 100 10"
                        className="text-yzh-gold"
                        stroke="currentColor"
                        style={{
                            strokeDasharray: arcLength,
                            animation:
                                'ls-stroke-draw 1000ms cubic-bezier(0.16, 1, 0.3, 1) 400ms both',
                            ['--ls-stroke-length' as string]: String(arcLength),
                        }}
                    />
                    {/* Anchor dots */}
                    <circle
                        cx="10"
                        cy="100"
                        r="2.5"
                        className="text-yzh-gold"
                        fill="currentColor"
                        style={fadeIn(400, 200)}
                    />
                    <circle
                        cx="100"
                        cy="10"
                        r="2.5"
                        className="text-yzh-gold"
                        fill="currentColor"
                        style={fadeIn(1400, 200)}
                    />
                </svg>

                {section && (
                    <p
                        className="mt-8 font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold"
                        style={fadeUp(1500, 240)}
                    >
                        {section} / Drawing
                    </p>
                )}
                <h1
                    className="mt-4 text-5xl font-semibold leading-[1.05] tracking-tight text-yzh-ink sm:text-6xl"
                    style={fadeUp(1700, 360)}
                    role="status"
                    aria-live="polite"
                >
                    {title}.
                </h1>
                <div
                    className="mt-10 inline-flex items-center gap-3"
                    style={fadeIn(2050, 280)}
                >
                    <span
                        className="h-1.5 w-1.5 rounded-full bg-yzh-gold"
                        style={dotPulse}
                        aria-hidden="true"
                    />
                    <span className="font-mono text-xs uppercase tracking-[0.22em] text-yzh-text">
                        Loading
                    </span>
                </div>
            </div>
        </div>
    );
}
