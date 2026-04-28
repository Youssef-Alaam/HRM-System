import Link from "next/link";
import { redirect } from "next/navigation";
import {
  Building2,
  GitBranch,
  Briefcase,
  Calendar,
  Users,
  ScrollText,
  type LucideIcon,
} from "lucide-react";
import { auth } from "@/lib/auth";
import { PageHeader } from "@/components/common/page-header";

const TILES: { href: string; label: string; icon: LucideIcon; hint: string }[] = [
  { href: "/settings/departments", label: "Departments", icon: GitBranch, hint: "Hierarchy + heads" },
  { href: "/settings/positions", label: "Positions", icon: Briefcase, hint: "Titles + levels" },
  { href: "/settings/offices", label: "Offices", icon: Building2, hint: "Locations + geofence radius" },
  { href: "/settings/holidays", label: "Holidays", icon: Calendar, hint: "Public holiday calendar" },
  { href: "/settings/users", label: "Users", icon: Users, hint: "Accounts + roles" },
  { href: "/settings/audit-log", label: "Audit log", icon: ScrollText, hint: "Every system action" },
];

export default async function SettingsPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== "admin") redirect("/");

  return (
    <>
      <PageHeader
        title="Settings"
        description="Configure your YZH-HR workspace."
      />
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {TILES.map((t) => {
          const Icon = t.icon;
          return (
            <Link
              key={t.href}
              href={t.href}
              className="rounded-lg border border-[var(--border)] bg-[var(--card)] p-5 hover:border-[var(--primary)] transition-colors"
            >
              <div className="flex items-start gap-3">
                <div className="h-10 w-10 rounded-md bg-[var(--primary)]/10 text-[var(--primary)] flex items-center justify-center">
                  <Icon className="h-5 w-5" />
                </div>
                <div>
                  <div className="font-medium">{t.label}</div>
                  <div className="text-xs text-[var(--muted-foreground)] mt-0.5">
                    {t.hint}
                  </div>
                </div>
              </div>
            </Link>
          );
        })}
      </div>
    </>
  );
}
