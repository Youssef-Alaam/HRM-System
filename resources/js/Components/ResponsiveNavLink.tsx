import { InertiaLinkProps, Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}: InertiaLinkProps & { active?: boolean }) {
    return (
        <Link
            {...props}
            className={`flex w-full items-center px-4 py-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold ${
                active
                    ? 'bg-yzh-ink-soft text-yzh-gold border-l-2 border-yzh-gold'
                    : 'border-l-2 border-transparent text-yzh-bone-soft hover:bg-yzh-ink-soft hover:text-yzh-gold'
            } ${className}`}
        >
            {children}
        </Link>
    );
}
