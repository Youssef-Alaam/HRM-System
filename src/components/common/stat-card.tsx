import { cn } from "@/lib/utils";
import type { LucideIcon } from "lucide-react";

export function StatCard({
  label,
  value,
  hint,
  icon: Icon,
  tone = "default",
  className,
}: {
  label: string;
  value: string | number;
  hint?: string;
  icon?: LucideIcon;
  tone?: "default" | "success" | "warning" | "danger" | "muted";
  className?: string;
}) {
  const toneClasses = {
    default: "text-[var(--primary)] bg-[var(--primary)]/10",
    success: "text-[var(--success)] bg-green-100",
    warning: "text-[var(--warning)] bg-amber-100",
    danger: "text-[var(--destructive)] bg-red-100",
    muted: "text-[var(--muted-foreground)] bg-[var(--muted)]",
  };

  return (
    <div
      className={cn(
        "rounded-lg border border-[var(--border)] bg-[var(--card)] p-4 flex items-center gap-3",
        className,
      )}
    >
      {Icon && (
        <div
          className={cn(
            "h-10 w-10 rounded-lg flex items-center justify-center",
            toneClasses[tone],
          )}
        >
          <Icon className="h-5 w-5" />
        </div>
      )}
      <div className="flex-1 min-w-0">
        <div className="text-xs uppercase tracking-wider text-[var(--muted-foreground)]">
          {label}
        </div>
        <div className="text-2xl font-semibold tracking-tight">{value}</div>
        {hint && (
          <div className="text-xs text-[var(--muted-foreground)] mt-0.5">
            {hint}
          </div>
        )}
      </div>
    </div>
  );
}
