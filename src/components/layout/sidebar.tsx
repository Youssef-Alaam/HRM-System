"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn, initials } from "@/lib/utils";
import { navForRole } from "@/lib/nav";
import type { Role } from "@/db/schema";

export function Sidebar({
  role,
  firstName,
  lastName,
}: {
  role: Role;
  firstName: string;
  lastName: string;
}) {
  const pathname = usePathname();
  const [collapsed, setCollapsed] = useState(false);
  const sections = navForRole(role);

  return (
    <aside
      className={cn(
        "shrink-0 flex flex-col bg-[var(--sidebar)] text-[var(--sidebar-foreground)] border-r border-[var(--sidebar-border)] transition-all",
        collapsed ? "w-[72px]" : "w-64",
      )}
    >
      <div className="h-14 flex items-center justify-between px-4 border-b border-[var(--sidebar-border)]">
        {!collapsed && (
          <Link href="/" className="flex items-baseline gap-1">
            <span className="text-[var(--primary)] font-bold text-xl tracking-tight">
              YZH
            </span>
            <span className="text-white/80 text-sm">HR</span>
          </Link>
        )}
        <button
          type="button"
          onClick={() => setCollapsed((c) => !c)}
          className="p-1.5 rounded-md hover:bg-white/5 text-[var(--sidebar-muted)]"
          aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"}
        >
          {collapsed ? (
            <ChevronRight className="h-4 w-4" />
          ) : (
            <ChevronLeft className="h-4 w-4" />
          )}
        </button>
      </div>

      <nav className="flex-1 overflow-y-auto py-4 space-y-6">
        {sections.map((section, sectionIdx) => (
          <div key={section.label ?? sectionIdx}>
            {!collapsed && section.label && (
              <div className="px-4 pb-2 text-[11px] uppercase tracking-wider text-[var(--sidebar-muted)]">
                {section.label}
              </div>
            )}
            <ul className="space-y-0.5 px-2">
              {section.items.map((item) => {
                const Icon = item.icon;
                const active =
                  pathname === item.href ||
                  (item.href !== "/" && pathname.startsWith(item.href));
                return (
                  <li key={item.href}>
                    <Link
                      href={item.href}
                      className={cn(
                        "flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors",
                        active
                          ? "bg-[var(--sidebar-active-bg)] text-[var(--sidebar-active-foreground)]"
                          : "text-[var(--sidebar-foreground)] hover:bg-white/5",
                        collapsed && "justify-center px-2",
                      )}
                      title={collapsed ? item.label : undefined}
                    >
                      <Icon className="h-4 w-4 shrink-0" />
                      {!collapsed && (
                        <span className="flex-1 truncate">{item.label}</span>
                      )}
                      {!collapsed && item.comingSoon && (
                        <span className="text-[10px] uppercase tracking-wider text-[var(--sidebar-muted)]">
                          Soon
                        </span>
                      )}
                    </Link>
                  </li>
                );
              })}
            </ul>
          </div>
        ))}
      </nav>

      <div className="border-t border-[var(--sidebar-border)] p-3">
        <Link
          href={`/employees/me`}
          className="flex items-center gap-3 rounded-md p-2 hover:bg-white/5"
        >
          <div className="h-8 w-8 rounded-full bg-[var(--primary)]/20 text-[var(--primary)] flex items-center justify-center text-xs font-semibold">
            {initials(`${firstName} ${lastName}`)}
          </div>
          {!collapsed && (
            <div className="flex-1 min-w-0">
              <div className="text-sm font-medium truncate">
                {firstName} {lastName}
              </div>
              <div className="text-[11px] text-[var(--sidebar-muted)] capitalize">
                {role}
              </div>
            </div>
          )}
        </Link>
      </div>
    </aside>
  );
}
