"use client";

import { useState } from "react";
import { LogOut, User as UserIcon } from "lucide-react";
import { signOut } from "next-auth/react";
import { initials } from "@/lib/utils";
import type { Role } from "@/db/schema";
import Link from "next/link";

export function Topbar({
  firstName,
  lastName,
  role,
}: {
  firstName: string;
  lastName: string;
  role: Role;
}) {
  const [open, setOpen] = useState(false);

  return (
    <header className="h-14 shrink-0 border-b border-[var(--border)] bg-[var(--background)] flex items-center justify-between px-4 sm:px-6">
      <div className="flex items-center gap-3">
        <span className="text-sm text-[var(--muted-foreground)] hidden sm:inline">
          {greetingFor(firstName)}
        </span>
      </div>
      <div className="relative">
        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          className="flex items-center gap-2 rounded-full p-1 hover:bg-[var(--accent)]"
        >
          <div className="h-8 w-8 rounded-full bg-[var(--primary)]/20 text-[var(--primary)] flex items-center justify-center text-xs font-semibold">
            {initials(`${firstName} ${lastName}`)}
          </div>
        </button>
        {open && (
          <>
            <button
              aria-label="Close menu"
              type="button"
              className="fixed inset-0 z-30"
              onClick={() => setOpen(false)}
            />
            <div className="absolute right-0 mt-2 w-56 rounded-md border border-[var(--border)] bg-[var(--card)] shadow-lg z-40 p-1">
              <div className="px-3 py-2 border-b border-[var(--border)]">
                <div className="text-sm font-medium truncate">
                  {firstName} {lastName}
                </div>
                <div className="text-xs text-[var(--muted-foreground)] capitalize">
                  {role}
                </div>
              </div>
              <Link
                href="/employees/me"
                className="flex items-center gap-2 px-3 py-2 text-sm rounded-md hover:bg-[var(--accent)]"
                onClick={() => setOpen(false)}
              >
                <UserIcon className="h-4 w-4" /> My profile
              </Link>
              <button
                type="button"
                onClick={() => signOut({ callbackUrl: "/login" })}
                className="w-full flex items-center gap-2 px-3 py-2 text-sm rounded-md hover:bg-[var(--accent)] text-left"
              >
                <LogOut className="h-4 w-4" /> Sign out
              </button>
            </div>
          </>
        )}
      </div>
    </header>
  );
}

function greetingFor(name: string): string {
  const hour = new Date().getHours();
  const greeting =
    hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening";
  return `${greeting}, ${name}`;
}
