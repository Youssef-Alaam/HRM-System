import { InertiaLinkProps, Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}: InertiaLinkProps & { active: boolean }) {
    return (
        <Link
            {...props}
            className={
                'relative inline-flex items-center px-1 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold rounded ' +
                (active
                    ? 'text-yzh-bone after:absolute after:inset-x-0 after:-bottom-[1px] after:h-0.5 after:bg-yzh-gold'
                    : 'text-yzh-bone-soft hover:text-yzh-gold') +
                ' ' +
                className
            }
        >
            {children}
        </Link>
    );
}
