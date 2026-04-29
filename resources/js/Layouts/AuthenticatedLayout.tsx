import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, X } from 'lucide-react';
import { PropsWithChildren, ReactNode, useState } from 'react';

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage().props.auth.user;
    const [mobileOpen, setMobileOpen] = useState(false);

    return (
        <div className="min-h-screen bg-yzh-bone">
            <nav className="bg-yzh-ink border-b border-yzh-ink-mute">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex items-center gap-10">
                            <Link
                                href="/"
                                className="flex items-baseline gap-2 outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold rounded"
                            >
                                <img
                                    src="/images/yzh-mark.png"
                                    alt="YZH"
                                    className="h-8 w-auto"
                                />
                                <span className="text-sm font-medium tracking-[0.3em] text-yzh-bone-soft">
                                    HR
                                </span>
                            </Link>

                            <div className="hidden items-center gap-6 sm:flex">
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Dashboard
                                </NavLink>
                            </div>
                        </div>

                        <div className="hidden items-center sm:flex">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button
                                        type="button"
                                        className="inline-flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-yzh-bone-soft transition-colors hover:bg-yzh-ink-soft hover:text-yzh-bone focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                                    >
                                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-yzh-gold/15 text-xs font-semibold text-yzh-gold">
                                            {initials(user.name)}
                                        </span>
                                        <span className="font-medium">
                                            {user.name}
                                        </span>
                                        <ChevronDown className="h-4 w-4 opacity-70" />
                                    </button>
                                </Dropdown.Trigger>

                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>
                                        Profile
                                    </Dropdown.Link>
                                    <Dropdown.Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                    >
                                        Log out
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                type="button"
                                onClick={() => setMobileOpen((v) => !v)}
                                className="inline-flex h-11 w-11 items-center justify-center rounded-md text-yzh-bone-soft transition-colors hover:bg-yzh-ink-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                                aria-label={
                                    mobileOpen ? 'Close menu' : 'Open menu'
                                }
                            >
                                {mobileOpen ? (
                                    <X className="h-5 w-5" />
                                ) : (
                                    <Menu className="h-5 w-5" />
                                )}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile drawer */}
                <div
                    className={
                        (mobileOpen ? 'block' : 'hidden') +
                        ' sm:hidden bg-yzh-ink border-t border-yzh-ink-mute'
                    }
                >
                    <div className="py-2">
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                        >
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <div className="border-t border-yzh-ink-mute py-3">
                        <div className="flex items-center gap-3 px-4 py-2">
                            <span className="flex h-9 w-9 items-center justify-center rounded-full bg-yzh-gold/15 text-sm font-semibold text-yzh-gold">
                                {initials(user.name)}
                            </span>
                            <div className="min-w-0">
                                <div className="truncate text-sm font-medium text-yzh-bone">
                                    {user.name}
                                </div>
                                <div className="truncate text-xs text-yzh-text">
                                    {user.email}
                                </div>
                            </div>
                        </div>

                        <div className="mt-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-b border-yzh-bone-soft bg-white">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}
