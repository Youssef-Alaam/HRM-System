import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Building2,
    Calendar,
    CalendarClock,
    ChevronDown,
    ClipboardList,
    Clock,
    FileText,
    Inbox,
    LayoutDashboard,
    LucideIcon,
    Menu,
    MessageSquare,
    PanelLeftClose,
    PanelLeftOpen,
    Plane,
    ScrollText,
    Settings,
    ShieldCheck,
    UserCog,
    Users,
    Wallet,
    X,
} from 'lucide-react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';

import Dropdown from '@/Components/Dropdown';

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
            { label: 'Departments', href: '/departments', icon: Building2, permission: 'employees.view.own' },
            { label: 'Positions', href: '/positions', icon: ClipboardList, permission: 'employees.view.own' },
            { label: 'Offices', href: '/offices', icon: Building2, permission: 'employees.view.own' },
        ],
    },
    {
        label: 'Time',
        items: [
            { label: 'Attendance', href: '/attendance', icon: Clock, permission: 'attendance.view.own' },
            { label: 'Schedules', href: '/schedules', icon: CalendarClock, permission: 'attendance.view.own' },
            { label: 'Holidays', href: '/holidays', icon: Calendar, permission: 'org.holidays.manage' },
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
    {
        label: 'Settings',
        items: [
            { label: 'Users & Roles', href: '/admin/users', icon: UserCog, permission: 'users.assign_roles' },
            { label: 'Audit Log', href: '/admin/audit-log', icon: ShieldCheck, permission: 'audit.view' },
            { label: 'System Settings', href: '/admin/settings', icon: Settings, permission: 'settings.edit' },
        ],
    },
];

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

function isActive(currentPath: string, href: string): boolean {
    if (href === '/dashboard') {
        return currentPath === '/dashboard';
    }

    return currentPath === href || currentPath.startsWith(href + '/');
}

function hasPermission(permissions: string[] | undefined, required?: string): boolean {
    if (!required) return true;
    if (!permissions) return false;
    return permissions.includes(required);
}

const SIDEBAR_COLLAPSED_KEY = 'yzh-hr.sidebar.collapsed';

export default function AppLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const page = usePage();
    const user = page.props.auth?.user;
    const currentPath = page.url.split('?')[0] ?? '/';

    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        try {
            const stored = window.localStorage.getItem(SIDEBAR_COLLAPSED_KEY);
            if (stored === '1') setCollapsed(true);
        } catch {
            /* localStorage unavailable */
        }
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

    return (
        <div className="min-h-screen bg-yzh-bone text-yzh-ink">
            {/* Mobile top bar */}
            <header className="lg:hidden sticky top-0 z-30 flex items-center justify-between bg-yzh-ink px-4 py-3 border-b border-yzh-ink-mute">
                <Link href="/dashboard" className="flex items-baseline gap-2">
                    <img src="/images/yzh-mark.png" alt="YZH" className="h-7 w-auto" />
                    <span className="text-xs font-medium tracking-[0.3em] text-yzh-bone-soft">HR</span>
                </Link>
                <button
                    type="button"
                    onClick={() => setMobileOpen(true)}
                    className="inline-flex h-11 w-11 items-center justify-center rounded-md text-yzh-bone-soft hover:bg-yzh-ink-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                    aria-label="Open menu"
                >
                    <Menu className="h-5 w-5" />
                </button>
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
                                className="flex items-baseline gap-2"
                                onClick={() => setMobileOpen(false)}
                            >
                                <img src="/images/yzh-mark.png" alt="YZH" className="h-7 w-auto" />
                                <span className="text-xs font-medium tracking-[0.3em] text-yzh-bone-soft">HR</span>
                            </Link>
                            <button
                                type="button"
                                onClick={() => setMobileOpen(false)}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-md text-yzh-bone-soft hover:bg-yzh-ink-soft"
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
                        <SidebarUserCard user={user} collapsed={false} />
                    </div>
                </div>
            )}

            <div className="flex min-h-screen">
                {/* Desktop sidebar — sticky so it stays put while the main column scrolls */}
                <aside
                    className={`hidden lg:flex sticky top-0 h-screen shrink-0 flex-col border-r border-yzh-ink-mute bg-yzh-ink text-yzh-bone-soft transition-[width] duration-200 ${collapsed ? 'w-16' : 'w-64'}`}
                >
                    <div className="flex items-center justify-between border-b border-yzh-ink-mute px-3 py-4">
                        {!collapsed && (
                            <Link href="/dashboard" className="flex items-baseline gap-2">
                                <img src="/images/yzh-mark.png" alt="YZH" className="h-7 w-auto" />
                                <span className="text-xs font-medium tracking-[0.3em] text-yzh-bone-soft">HR</span>
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

                    <SidebarUserCard user={user} collapsed={collapsed} />
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    {header && (
                        <header className="border-b border-yzh-bone-soft bg-white">
                            <div className="px-4 py-6 sm:px-6 lg:px-8">{header}</div>
                        </header>
                    )}
                    <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">{children}</main>
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
                            const active = isActive(currentPath, item.href);
                            return (
                                <li key={item.href}>
                                    <Link
                                        href={item.href}
                                        onClick={onNavigate}
                                        title={collapsed ? item.label : undefined}
                                        className={`group flex items-center gap-3 rounded-md px-2 py-2 text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold ${
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

function SidebarUserCard({
    user,
    collapsed,
}: {
    user: { name: string; email: string; role?: string } | null | undefined;
    collapsed: boolean;
}) {
    if (!user) return null;

    return (
        <div className="border-t border-yzh-ink-mute p-3">
            <Dropdown>
                <Dropdown.Trigger>
                    <button
                        type="button"
                        className={`flex w-full items-center gap-3 rounded-md p-2 text-left text-yzh-bone-soft transition-colors hover:bg-yzh-ink-soft hover:text-yzh-bone focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold ${collapsed ? 'justify-center' : ''}`}
                    >
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-yzh-gold/15 text-xs font-semibold text-yzh-gold">
                            {initials(user.name)}
                        </span>
                        {!collapsed && (
                            <>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium text-yzh-bone">
                                        {user.name}
                                    </span>
                                    <span className="block truncate text-xs uppercase tracking-wider text-yzh-text">
                                        {user.role ?? 'no role'}
                                    </span>
                                </span>
                                <ChevronDown className="h-4 w-4 opacity-70" />
                            </>
                        )}
                    </button>
                </Dropdown.Trigger>
                <Dropdown.Content align="left">
                    <div className="px-4 py-3 text-xs text-gray-600">
                        <div className="font-medium text-gray-900">{user.name}</div>
                        <div className="truncate">{user.email}</div>
                    </div>
                    <Dropdown.Link href="/profile">Profile</Dropdown.Link>
                    <Dropdown.Link href="/logout" method="post" as="button">
                        Log out
                    </Dropdown.Link>
                </Dropdown.Content>
            </Dropdown>
        </div>
    );
}
