import { Card, CardContent } from "@/components/ui/card";
import { Sparkles, type LucideIcon } from "lucide-react";

export function ComingSoonCard({
  label,
  detail,
  icon: Icon = Sparkles,
}: {
  label: string;
  detail?: string;
  icon?: LucideIcon;
}) {
  return (
    <Card className="border-dashed">
      <CardContent className="p-5 flex items-start gap-3">
        <div className="h-10 w-10 rounded-lg bg-[var(--accent)] text-[var(--accent-foreground)] flex items-center justify-center">
          <Icon className="h-5 w-5" />
        </div>
        <div className="flex-1">
          <div className="flex items-center gap-2">
            <span className="font-medium">{label}</span>
            <span className="text-[10px] uppercase tracking-wider rounded-full bg-[var(--muted)] text-[var(--muted-foreground)] px-2 py-0.5">
              Coming Soon
            </span>
          </div>
          {detail && (
            <p className="text-sm text-[var(--muted-foreground)] mt-1">
              {detail}
            </p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
