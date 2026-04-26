import { desc } from "drizzle-orm";
import Link from "next/link";
import { ScrollText, Users, ShieldCheck } from "lucide-react";
import { db } from "@/db";
import { auditLogs, employees } from "@/db/schema";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/page-header";
import { StatCard } from "@/components/stat-card";
import { formatDateTimeDisplay } from "@/lib/utils";
import { HrDashboard } from "./hr-dashboard";

export async function AdminDashboard({ userId }: { userId: string }) {
  const me = await db.query.employees.findFirst({
    where: (e, { eq }) => eq(e.id, userId),
  });
  if (!me) return null;

  const totalUsers = await db.select().from(employees);
  const recentActivity = await db
    .select()
    .from(auditLogs)
    .orderBy(desc(auditLogs.createdAt))
    .limit(8);

  return (
    <>
      <PageHeader
        title="Admin overview"
        description="System-wide HR metrics and activity."
      />

      <div className="grid gap-3 sm:grid-cols-3 mb-6">
        <StatCard
          label="Total accounts"
          value={totalUsers.length}
          icon={Users}
          tone="default"
        />
        <StatCard
          label="Audit events"
          value={recentActivity.length}
          icon={ScrollText}
          tone="muted"
          hint="Last 8 shown below"
        />
        <StatCard
          label="System status"
          value="OK"
          icon={ShieldCheck}
          tone="success"
        />
      </div>

      <Card className="mb-8">
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <ScrollText className="h-4 w-4 text-[var(--primary)]" />
            Recent audit activity
          </CardTitle>
          <Button asChild variant="link" size="sm">
            <Link href="/settings/audit-log">View all</Link>
          </Button>
        </CardHeader>
        <CardContent>
          {recentActivity.length === 0 ? (
            <div className="text-sm text-[var(--muted-foreground)] py-4">
              No activity yet.
            </div>
          ) : (
            <ul className="divide-y divide-[var(--border)]">
              {recentActivity.map((event) => (
                <li
                  key={event.id}
                  className="py-2 flex items-center justify-between text-sm"
                >
                  <div className="flex items-center gap-3 min-w-0">
                    <span className="font-medium capitalize shrink-0">
                      {event.action}
                    </span>
                    <span className="text-[var(--muted-foreground)] truncate">
                      {event.entityType}
                      {event.entityId && ` · ${event.entityId.slice(0, 8)}`}
                    </span>
                  </div>
                  <span className="text-xs text-[var(--muted-foreground)] shrink-0">
                    {formatDateTimeDisplay(event.createdAt)}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>

      <HrDashboard orgId={me.orgId} />
    </>
  );
}
