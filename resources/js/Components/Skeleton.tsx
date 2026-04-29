import { HTMLAttributes } from 'react';

type Props = HTMLAttributes<HTMLDivElement>;

/**
 * Base skeleton block. Pulses gently when motion is allowed, sits flat under
 * `prefers-reduced-motion`. Compose your own shape via className: heights,
 * widths, and radii. Use a preset (SkeletonCard / SkeletonText) when one
 * matches the layout already.
 *
 * @example
 *   <Skeleton className="h-4 w-32 rounded" />
 */
export default function Skeleton({
    className = '',
    'aria-label': ariaLabel,
    ...rest
}: Props) {
    return (
        <div
            role="status"
            aria-label={ariaLabel ?? 'Loading'}
            aria-live="polite"
            {...rest}
            className={`bg-yzh-bone-soft motion-safe:animate-pulse ${className}`}
        />
    );
}

/**
 * Multi-line text placeholder. Final line is shorter to mimic natural prose.
 */
export function SkeletonText({
    lines = 3,
    className = '',
}: {
    lines?: number;
    className?: string;
}) {
    return (
        <div
            className={`space-y-2 ${className}`}
            role="status"
            aria-label="Loading content"
        >
            {Array.from({ length: lines }).map((_, i) => (
                <Skeleton
                    key={i}
                    className={`h-3 rounded ${i === lines - 1 ? 'w-2/3' : 'w-full'}`}
                />
            ))}
        </div>
    );
}

/**
 * Card-shaped placeholder that mirrors the Dashboard "Coming up" tiles.
 * Drop-in replacement when those cards are loading.
 */
export function SkeletonCard() {
    return (
        <div className="rounded-lg border border-yzh-bone-soft bg-white p-5 shadow-sm">
            <Skeleton className="h-10 w-10 rounded-md" />
            <Skeleton className="mt-4 h-3.5 w-24 rounded" />
            <Skeleton className="mt-2 h-3 w-full rounded" />
            <Skeleton className="mt-1 h-3 w-3/4 rounded" />
            <Skeleton className="mt-4 h-2 w-12 rounded" />
        </div>
    );
}

/**
 * Single-line row placeholder for table-like layouts.
 */
export function SkeletonRow({ className = '' }: { className?: string }) {
    return (
        <div className={`flex items-center gap-3 ${className}`}>
            <Skeleton className="h-9 w-9 rounded-full" />
            <div className="flex-1 space-y-2">
                <Skeleton className="h-3 w-1/3 rounded" />
                <Skeleton className="h-3 w-2/3 rounded" />
            </div>
        </div>
    );
}
