import type { Role } from "@/db/schema";
import {
  LayoutDashboard,
  Users,
  CalendarClock,
  CalendarCheck,
  Inbox,
  Building2,
  Settings,
  ScrollText,
  GitBranch,
  Banknote,
  ClipboardList,
  PackageSearch,
  Sparkles,
  type LucideIcon,
} from "lucide-react";

export type NavItem = {
  label: string;
  href: string;
  icon: LucideIcon;
  roles: Role[];
  comingSoon?: boolean;
};

export type NavSection = {
  label?: string;
  items: NavItem[];
};

export const navSections: NavSection[] = [
  {
    items: [
      {
        label: "Dashboard",
        href: "/",
        icon: LayoutDashboard,
        roles: ["admin", "hr", "manager", "employee"],
      },
    ],
  },
  {
    label: "People",
    items: [
      {
        label: "Employees",
        href: "/employees",
        icon: Users,
        roles: ["admin", "hr", "manager"],
      },
      {
        label: "Org Chart",
        href: "/org-chart",
        icon: GitBranch,
        roles: ["admin", "hr", "manager", "employee"],
      },
    ],
  },
  {
    label: "Time",
    items: [
      {
        label: "My Schedule",
        href: "/schedule",
        icon: CalendarClock,
        roles: ["admin", "hr", "manager", "employee"],
      },
      {
        label: "Attendance",
        href: "/attendance",
        icon: CalendarCheck,
        roles: ["admin", "hr", "manager", "employee"],
      },
    ],
  },
  {
    label: "Leave",
    items: [
      {
        label: "My Leave",
        href: "/leave",
        icon: ClipboardList,
        roles: ["admin", "hr", "manager", "employee"],
      },
      {
        label: "Approvals",
        href: "/leave/inbox",
        icon: Inbox,
        roles: ["admin", "hr", "manager"],
      },
      {
        label: "Leave Calendar",
        href: "/leave/calendar",
        icon: CalendarCheck,
        roles: ["admin", "hr", "manager", "employee"],
      },
    ],
  },
  {
    label: "Coming Soon",
    items: [
      {
        label: "Payroll",
        href: "/coming-soon/payroll",
        icon: Banknote,
        roles: ["admin", "hr", "employee"],
        comingSoon: true,
      },
      {
        label: "Assets",
        href: "/coming-soon/assets",
        icon: PackageSearch,
        roles: ["admin", "hr", "manager", "employee"],
        comingSoon: true,
      },
      {
        label: "Performance",
        href: "/coming-soon/performance",
        icon: ScrollText,
        roles: ["admin", "hr", "manager", "employee"],
        comingSoon: true,
      },
      {
        label: "AI Assistant",
        href: "/coming-soon/ai",
        icon: Sparkles,
        roles: ["admin", "hr", "manager", "employee"],
        comingSoon: true,
      },
    ],
  },
  {
    label: "Admin",
    items: [
      {
        label: "Settings",
        href: "/settings",
        icon: Settings,
        roles: ["admin"],
      },
      {
        label: "Audit Log",
        href: "/settings/audit-log",
        icon: ScrollText,
        roles: ["admin"],
      },
      {
        label: "Offices",
        href: "/settings/offices",
        icon: Building2,
        roles: ["admin"],
      },
    ],
  },
];

export function navForRole(role: Role): NavSection[] {
  return navSections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => item.roles.includes(role)),
    }))
    .filter((section) => section.items.length > 0);
}
