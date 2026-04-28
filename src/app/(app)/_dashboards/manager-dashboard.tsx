import { eq, and, count } from "drizzle-orm";
import Link from "next/link";
import { Users, Inbox, Clock, PackageSearch } from "lucide-react";
import { db } from "@/db";
import { employees, attendanceRecords, leaveRequests } from "@/db/schema";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/common/page-header";
import { StatCard } from "@/components/common/stat-card";
import { ComingSoonCard } from "@/components/common/coming-soon-card";

export async function ManagerDashboard({ managerId }: { managerId: string }) {
  const todayStr = new Date().toISOString().slice(0, 10);

  const team = await db
    .select()
    .from(employees)
    .where(
      and(eq(employees.managerId, managerId), eq(employees.employmentStatus, "active")),
    );

  const teamIds = team.map((t) => t.id);
  let presentCount = 0;
  let lateCount = 0;
  let onLeaveCount = 0;

  if (teamIds.length > 0) {
    const todayRecords = await db
      .select()
      .from(attendanceRecords)
      .where(eq(attendanceRecords.date, todayStr));
    for (const r of todayRecords) {
      if (!teamIds.includes(r.employeeId)) continue;
      if (r.status === "late") lateCount++;
      else if (r.status === "ontime") presentCount++;
      else if (r.status === "on_leave") onLeaveCount++;
    }
  }

  const pendingApprovalsCount = await db
    .select({ value: count() })
    .from(leaveRequests)
    .where(
      and(
        eq(leaveRequests.managerId, managerId),
        eq(leaveRequests.status, "pending"),
      ),
    );

  return (
    <>
      <PageHeader
        title="Team overview"
        description="What your team is up to today."
      />

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <StatCard
          label="Team size"
          value={team.length}
          icon={Users}
          tone="default"
        />
        <StatCard
          label="Present"
          value={presentCount}
          icon={Clock}
          tone="success"
        />
        <StatCard
          label="Late"
          value={lateCount}
          icon={Clock}
          tone="warning"
        />
        <StatCard
          label="On leave"
          value={onLeaveCount}
          icon={Clock}
          tone="muted"
        />
      </div>

      <div className="grid gap-4 lg:grid-cols-2 mb-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Inbox className="h-4 w-4 text-[var(--primary)]" />
              Pending approvals
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center justify-between">
              <div>
                <div className="text-3xl font-semibold">
                  {pendingApprovalsCount[0]?.value ?? 0}
                </div>
                <div className="text-xs text-[var(--muted-foreground)] mt-1">
                  Leave requests awaiting your decision
                </div>
              </div>
              <Button asChild>
                <Link href="/leave/inbox">Open inbox</Link>
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Users className="h-4 w-4 text-[var(--primary)]" />
              Direct reports
            </CardTitle>
          </CardHeader>
          <CardContent>
            {team.length === 0 ? (
              <div className="text-sm text-[var(--muted-foreground)] py-4">
                No direct reports yet.
              </div>
            ) : (
              <ul className="divide-y divide-[var(--border)]">
                {team.slice(0, 5).map((member) => (
                  <li
                    key={member.id}
                    className="py-2.5 flex items-center justify-between"
                  >
                    <Link
                      href={`/employees/${member.id}`}
                      className="text-sm hover:underline"
                    >
                      {member.firstName} {member.lastName}
                    </Link>
                    <Badge variant="outline" className="text-[10px]">
                      {member.employeeCode}
                    </Badge>
                  </li>
                ))}
              </ul>
            )}
            {team.length > 5 && (
              <Button asChild variant="link" className="px-0 mt-2">
                <Link href="/employees">View all {team.length}</Link>
              </Button>
            )}
          </CardContent>
        </Card>
      </div>

      <h2 className="text-sm uppercase tracking-wider text-[var(--muted-foreground)] mb-3">
        Coming soon
      </h2>
      <div className="grid gap-3 md:grid-cols-2">
        <ComingSoonCard
          label="Team performance"
          detail="Reviews, goals, and 1-on-1 tracking."
        />
        <ComingSoonCard
          icon={PackageSearch}
          label="Asset assignments"
          detail="See what equipment your team has been issued."
        />
      </div>
    </>
  );
}
