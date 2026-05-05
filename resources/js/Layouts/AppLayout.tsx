import { Link, router, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Boxes,
    Building2,
    Calendar,
    CalendarClock,
    ClipboardList,
    Clock,
    FileText,
    FolderOpen,
    Inbox,
    LayoutDashboard,
    LucideIcon,
    Menu,
    MessageSquare,
    Network,
    PanelLeftClose,
    PanelLeftOpen,
    Plane,
    ScrollText,
    Settings,
    ShieldCheck,
    Tag,
    UserCog,
    Users,
    Wallet,
    X,
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';

import Dropdown from '@/Components/Dropdown';
import GlobalSearch from '@/Components/GlobalSearch';
import LoadingScreen from '@/Components/LoadingScreen';

type NavItem = {
    label: string;
    href: string;
    icon: LucideIcon;
    permission?: string;
};

type NavGroup = {
    label: string;
    items: NavItem[];
};

/*
| Sidebar groups (locked 2026-04-30 with Walid):
| - Departments / Positions / Offices / Holiday calendar moved out of the
|   sidebar into the Settings dropdown — sidebar stays tight, configuration
|   surfaces are admin-only.
| - Org chart is now a peer of Employees in the People group.
| - Documents is HR + Admin only (gated server-side; sidebar item hidden
|   for everyone else via permission filter).
| - Assets is visible to all roles but the controller scopes the data
|   per role (manager sees own only, etc.) — no permission gate on the
|   sidebar item.
*/
const NAV_GROUPS: NavGroup[] = [
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        ],
    },
    {
        label: 'People',
        items: [
            { label: 'Employees', href: '/employees', icon: Users, permission: 'employees.view.own' },
            { label: 'Org chart', href: '/org-chart', icon: Network },
            { label: 'Documents', href: '/documents', icon: FolderOpen, permission: 'documents.view.any' },
            { label: 'Assets', href: '/assets', icon: Boxes },
        ],
    },
    {
        label: 'Time',
        items: [
            { label: 'Attendance', href: '/attendance', icon: Clock, permission: 'attendance.view.own' },
            { label: 'Schedules', href: '/schedules', icon: CalendarClock, permission: 'attendance.view.own' },
        ],
    },
    {
        label: 'Leave',
        items: [
            { label: 'Leave Requests', href: '/leave', icon: Plane, permission: 'leave.request.own' },
            { label: 'Leave Balances', href: '/leave/balances', icon: ScrollText, permission: 'leave.view.own' },
        ],
    },
    {
        label: 'Requests',
        items: [
            { label: 'My Requests', href: '/requests', icon: Inbox, permission: 'requests.create.own' },
        ],
    },
    {
        label: 'Payroll',
        items: [
            { label: 'Payroll Runs', href: '/payroll', icon: Wallet, permission: 'payroll.run' },
            { label: 'Payslips', href: '/payslips', icon: FileText, permission: 'payroll.payslip.view.own' },
        ],
    },
    {
        label: 'Reports',
        items: [
            { label: 'Reports', href: '/reports', icon: BarChart3, permission: 'reports.run.own' },
        ],
    },
    {
        label: 'Communication',
        items: [
            { label: 'Messages', href: '/messages', icon: MessageSquare, permission: 'chat.send' },
            { label: 'Announcements', href: '/announcements', icon: ScrollText, permission: 'announcements.view' },
        ],
    },
];

/*
| Admin Settings dropdown (top-right user menu, hidden from non-admins).
| Order is the source-of-truth for the drafting-set section identifier
| `S.0X` shown in the loading-screen kicker.
*/
const ADMIN_MENU_ITEMS: NavItem[] = [
    { label: 'Departments', href: '/departments', icon: Building2, permission: 'org.departments.manage' },
    { label: 'Positions', href: '/positions', icon: ClipboardList, permission: 'org.positions.manage' },
    { label: 'Offices', href: '/offices', icon: Building2, permission: 'org.offices.manage' },
    { label: 'Holiday calendar', href: '/holidays', icon: Calendar, permission: 'org.holidays.manage' },
    { label: 'Document types', href: '/admin/document-types', icon: Tag, permission: 'settings.document_types.manage' },
    { label: 'Asset categories', href: '/admin/asset-categories', icon: Tag, permission: 'settings.asset_categories.manage' },
    { label: 'Users & Roles', href: '/admin/users', icon: UserCog, permission: 'users.assign_roles' },
    { label: 'Audit Log', href: '/admin/audit-log', icon: ShieldCheck, permission: 'audit.view' },
    { label: 'System Settings', href: '/admin/settings', icon: Settings, permission: 'settings.edit' },
];

/*
|--------------------------------------------------------------------------
| Route registry — URL → loading-screen metadata
|--------------------------------------------------------------------------
| Built once from NAV_GROUPS + ADMIN_MENU_ITEMS so the loading screen can
| resolve the destination's title + section identifier before the new page
| has rendered. The section is `{group letter}.0{position}` (A.01, B.02, …)
| derived from the order in NAV_GROUPS — drafting-set notation.
*/
type RouteMeta = { title: string; section: string };

const SECTION_LETTERS = 'ABCDEFGHIJKLMN';

const ROUTE_REGISTRY: Record<string, RouteMeta> = (() => {
    const reg: Record<string, RouteMeta> = {};
    NAV_GROUPS.forEach((group, gi) => {
        const letter = SECTION_LETTERS[gi] ?? 'X';
        group.items.forEach((item, ii) => {
            reg[item.href] = {
                title: item.label,
                section: `${letter}.${String(ii + 1).padStart(2, '0')}`,
            };
        });
    });
    ADMIN_MENU_ITEMS.forEach((item, ii) => {
        reg[item.href] = {
            title: item.label,
            section: `S.${String(ii + 1).padStart(2, '0')}`,
        };
    });
    // Routes outside the sidebar that still benefit from the loading screen.
    reg['/profile'] = { title: 'Profile', section: 'P.01' };
    return reg;
})();

/**
 * Resolve an Inertia visit URL to a registry entry. Returns null for routes
 * we don't track (which fall through to no loading screen — the page just
 * renders when ready).
 */
function lookupRoute(rawUrl: string): RouteMeta | null {
    try {
        const path = new URL(rawUrl, window.location.origin).pathname;
        return ROUTE_REGISTRY[path] ?? null;
    } catch {
        return null;
    }
}

/**
 * Same-pathname check. Used to skip the full-page loading screen when an
 * Inertia visit only refines query params (search, filter, pagination) on
 * the current page — those refreshes mount inline UI on the page itself
 * (e.g. a pulsing dot next to the results count), not the Compass Arc.
 */
function isSamePathnameAs(rawUrl: string, currentPath: string): boolean {
    try {
        return new URL(rawUrl, window.location.origin).pathname === currentPath;
    } catch {
        return false;
    }
}

const NAV_LOADING_DEBOUNCE_MS = 200;

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

/**
 * Longest-prefix match. Without this, /leave/balances matches BOTH the
 * /leave parent and /leave/balances itself — so two siblings light up.
 * Pre-compute the active href once per render against all hrefs in nav.
 */
function computeActiveHref(currentPath: string, allHrefs: string[]): string | null {
    let best: string | null = null;
    for (const href of allHrefs) {
        const matches =
            href === '/dashboard'
                ? currentPath === '/dashboard'
                : currentPath === href || currentPath.startsWith(href + '/');
        if (matches && (!best || href.length > best.length)) {
            best = href;
        }
    }
    return best;
}

function hasPermission(permissions: string[] | undefined, required?: string): boolean {
    if (!required) return true;
    if (!permissions) return false;
    return permissions.includes(required);
}

const SIDEBAR_COLLAPSED_KEY = 'yzh-hr.sidebar.collapsed';

type AppUser = {
    id: number;
    name: string;
    email: string;
    role?: string;
    permissions?: string[];
};

export default function AppLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const page = usePage();
    const user = page.props.auth?.user as AppUser | null | undefined;
    const currentPath = page.url.split('?')[0] ?? '/';

    // Lazy initializer reads localStorage on first render so the sidebar
    // never paints its expanded state before snapping closed (fixes the
    // flicker that happened when navigating into the app while collapsed).
    const [collapsed, setCollapsed] = useState<boolean>(() => {
        if (typeof window === 'undefined') return false;
        try {
            return window.localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1';
        } catch {
            return false;
        }
    });
    const [mobileOpen, setMobileOpen] = useState(false);

    // Navigation loading state.
    // 'start' fires when an Inertia visit begins; we resolve the destination's
    // metadata, debounce 200ms (so fast loads don't flicker), then show the
    // loading screen. 'finish' clears it (success or error).
    const [navigatingTo, setNavigatingTo] = useState<RouteMeta | null>(null);

    useEffect(() => {
        let timer: ReturnType<typeof setTimeout> | null = null;

        const offStart = router.on('start', (event) => {
            const url = event.detail.visit.url.toString();
            // Same-page refreshes (search debounce, filter, pagination) keep
            // the current page mounted and run their own scoped loading UI.
            // Skip the full-page Compass Arc here so the search input doesn't
            // unmount mid-type.
            if (isSamePathnameAs(url, window.location.pathname)) return;
            const meta = lookupRoute(url);
            if (!meta) return;
            if (timer) clearTimeout(timer);
            timer = setTimeout(() => {
                setNavigatingTo(meta);
            }, NAV_LOADING_DEBOUNCE_MS);
        });

        const offFinish = router.on('finish', () => {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
            setNavigatingTo(null);
            // Close the mobile drawer if it was open while navigating.
            setMobileOpen(false);
        });

        return () => {
            if (timer) clearTimeout(timer);
            offStart();
            offFinish();
        };
    }, []);

    const toggleCollapsed = () => {
        setCollapsed((prev) => {
            const next = !prev;
            try {
                window.localStorage.setItem(SIDEBAR_COLLAPSED_KEY, next ? '1' : '0');
            } catch {
                /* localStorage unavailable */
            }
            return next;
        });
    };

    const visibleGroups = NAV_GROUPS.map((group) => ({
        ...group,
        items: group.items.filter((item) => hasPermission(user?.permissions, item.permission)),
    })).filter((group) => group.items.length > 0);

    const adminItems = ADMIN_MENU_ITEMS.filter((item) =>
        hasPermission(user?.permissions, item.permission),
    );

    return (
        <div className="min-h-screen bg-yzh-bone text-yzh-ink">
            {/* Mobile top bar */}
            <header className="lg:hidden sticky top-0 z-30 flex items-center justify-between bg-yzh-ink px-4 py-3 border-b border-yzh-ink-mute">
                <Link href="/dashboard">
                    <img src="/images/yzh-mark.png" alt="YZH Solutions" className="h-7 w-auto" />
                </Link>
                <div className="flex items-center gap-1">
                    {user && <UserMenu user={user} adminItems={adminItems} dark />}
                    <button
                        type="button"
                        onClick={() => setMobileOpen(true)}
                        className="inline-flex h-11 w-11 items-center justify-center rounded-md text-yzh-bone-soft hover:bg-yzh-ink-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                        aria-label="Open menu"
                    >
                        <Menu className="h-5 w-5" />
                    </button>
                </div>
            </header>

            {/* Mobile drawer */}
            {mobileOpen && (
                <div className="lg:hidden fixed inset-0 z-40 flex">
                    <button
                        type="button"
                        className="fixed inset-0 bg-black/60"
                        aria-label="Close menu"
                        onClick={() => setMobileOpen(false)}
                    />
                    <div className="relative z-50 flex w-72 max-w-full flex-col bg-yzh-ink">
                        <div className="flex items-center justify-between border-b border-yzh-ink-mute px-4 py-3">
                            <Link
                                href="/dashboard"
                                onClick={() => setMobileOpen(false)}
                            >
                                <img src="/images/yzh-mark.png" alt="YZH Solutions" className="h-7 w-auto" />
                            </Link>
                            <button
                                type="button"
                                onClick={() => setMobileOpen(false)}
                                className="inline-flex h-11 w-11 items-center justify-center rounded-md text-yzh-bone-soft hover:bg-yzh-ink-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                                aria-label="Close menu"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <SidebarNav
                            groups={visibleGroups}
                            currentPath={currentPath}
                            collapsed={false}
                            onNavigate={() => setMobileOpen(false)}
                        />
                    </div>
                </div>
            )}

            <div className="flex min-h-screen">
                {/* Desktop sidebar — sticky so it stays put while the main column scrolls */}
                <aside
                    className={`hidden lg:flex sticky top-0 h-screen shrink-0 flex-col border-r border-yzh-ink-mute bg-yzh-ink text-yzh-bone-soft motion-safe:transition-[width] motion-safe:duration-300 motion-safe:ease-out ${collapsed ? 'w-16' : 'w-64'}`}
                >
                    <div className="flex items-center justify-between border-b border-yzh-ink-mute px-3 py-4">
                        {!collapsed && (
                            <Link href="/dashboard">
                                <img src="/images/yzh-mark.png" alt="YZH Solutions" className="h-7 w-auto" />
                            </Link>
                        )}
                        <button
                            type="button"
                            onClick={toggleCollapsed}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-md text-yzh-bone-soft hover:bg-yzh-ink-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                            aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                            title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                        >
                            {collapsed ? (
                                <PanelLeftOpen className="h-4 w-4" />
                            ) : (
                                <PanelLeftClose className="h-4 w-4" />
                            )}
                        </button>
                    </div>

                    <SidebarNav
                        groups={visibleGroups}
                        currentPath={currentPath}
                        collapsed={collapsed}
                    />
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    {/* Desktop top bar — page header on left, user menu on right.
                        During an Inertia visit the header is replaced with the
                        destination's metadata so the user immediately sees
                        where they're headed. */}
                    <header className="hidden lg:flex sticky top-0 z-20 items-center gap-4 border-b border-yzh-bone-soft bg-white px-6 py-4 lg:px-8">
                        <div className="min-w-0 flex-1">
                            {navigatingTo ? (
                                <NavigatingHeader meta={navigatingTo} />
                            ) : (
                                header
                            )}
                        </div>
                        <GlobalSearch />
                        {user && <UserMenu user={user} adminItems={adminItems} />}
                    </header>

                    {/* Mobile shows the page header below the mobile top bar
                        (since mobile top bar holds the user menu). */}
                    {(header || navigatingTo) && (
                        <div className="lg:hidden border-b border-yzh-bone-soft bg-white px-4 py-6 sm:px-6">
                            {navigatingTo ? (
                                <NavigatingHeader meta={navigatingTo} />
                            ) : (
                                header
                            )}
                        </div>
                    )}

                    <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                        {navigatingTo ? (
                            <LoadingScreen
                                title={navigatingTo.title}
                                section={navigatingTo.section}
                            />
                        ) : (
                            children
                        )}
                    </main>
                </div>
            </div>
        </div>
    );
}

function SidebarNav({
    groups,
    currentPath,
    collapsed,
    onNavigate,
}: {
    groups: NavGroup[];
    currentPath: string;
    collapsed: boolean;
    onNavigate?: () => void;
}) {
    const allHrefs = groups.flatMap((g) => g.items.map((i) => i.href));
    const activeHref = computeActiveHref(currentPath, allHrefs);

    return (
        <nav
            className="flex-1 overflow-y-auto py-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            style={{ msOverflowStyle: 'none' }}
        >
            {groups.map((group) => (
                <div key={group.label} className="px-3 pb-3">
                    {!collapsed && (
                        <div className="mb-1 px-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-yzh-text">
                            {group.label}
                        </div>
                    )}
                    <ul className="space-y-1">
                        {group.items.map((item) => {
                            const active = item.href === activeHref;
                            return (
                                <li key={item.href}>
                                    <Link
                                        href={item.href}
                                        onClick={onNavigate}
                                        preserveScroll
                                        title={collapsed ? item.label : undefined}
                                        className={`group flex items-center gap-3 rounded-md px-2 py-3 text-sm transition-colors duration-150 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold lg:py-2 ${
                                            active
                                                ? 'bg-yzh-gold/15 text-yzh-gold'
                                                : 'text-yzh-bone-soft hover:bg-yzh-ink-soft hover:text-yzh-bone'
                                        }`}
                                    >
                                        <item.icon
                                            className={`h-4 w-4 shrink-0 ${active ? 'text-yzh-gold' : 'text-yzh-bone-soft group-hover:text-yzh-bone'}`}
                                        />
                                        {!collapsed && <span className="truncate">{item.label}</span>}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            ))}
        </nav>
    );
}

/**
 * Header rendered while navigating to a tracked route — small drafting-set
 * section identifier above the destination title. Replaces the current page's
 * header until `router.on('finish')` fires.
 */
function NavigatingHeader({ meta }: { meta: RouteMeta }) {
    return (
        <div className="flex flex-col gap-1">
            <p className="font-mono text-xs uppercase tracking-[0.22em] text-yzh-gold">
                {meta.section}
            </p>
            <h1 className="text-2xl font-semibold tracking-tight text-yzh-ink">
                {meta.title}
            </h1>
        </div>
    );
}

/**
 * Top-right user menu. Click the avatar → dropdown opens downward (anchored to
 * the right edge), so it never gets clipped against the bottom of the viewport.
 * Includes admin-only items (Users & Roles, Audit Log, System Settings) when
 * the user has the corresponding permissions.
 */
function UserMenu({
    user,
    adminItems,
    dark = false,
}: {
    user: AppUser;
    adminItems: NavItem[];
    dark?: boolean;
}) {
    const triggerClasses = dark
        ? 'flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-yzh-bone-soft transition-colors hover:bg-yzh-ink-soft hover:text-yzh-bone focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold'
        : 'flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-yzh-ink transition-colors hover:bg-yzh-bone focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold';

    return (
        <Dropdown>
            <Dropdown.Trigger>
                <button type="button" className={triggerClasses} aria-label="Open user menu">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-yzh-gold/15 text-xs font-semibold text-yzh-gold">
                        {initials(user.name)}
                    </span>
                    <span className="hidden lg:flex flex-col items-start leading-tight">
                        <span className="text-sm font-medium">{user.name}</span>
                        <span className="text-[10px] uppercase tracking-widest text-yzh-text">
                            {user.role ?? 'no role'}
                        </span>
                    </span>
                </button>
            </Dropdown.Trigger>
            <Dropdown.Content align="right">
                <div className="px-4 py-3 text-xs">
                    <div className="font-medium text-yzh-ink">{user.name}</div>
                    <div className="truncate text-yzh-slate">{user.email}</div>
                </div>
                <div className="border-t border-yzh-bone-soft" />
                <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                {adminItems.length > 0 && (
                    <>
                        <div className="border-t border-yzh-bone-soft" />
                        <div className="px-4 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-yzh-text">
                            Admin
                        </div>
                        {adminItems.map((item) => (
                            <Dropdown.Link key={item.href} href={item.href}>
                                {item.label}
                            </Dropdown.Link>
                        ))}
                    </>
                )}
                <div className="border-t border-yzh-bone-soft" />
                <Dropdown.Link href="/logout" method="post" as="button">
                    Log out
                </Dropdown.Link>
            </Dropdown.Content>
        </Dropdown>
    );
}
