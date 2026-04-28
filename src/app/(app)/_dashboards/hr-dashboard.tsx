import { eq, and, count } from "drizzle-orm";
import Link from "next/link";
import {
  Users,
  Clock,
  Inbox,
  AlertTriangle,
  Banknote,
  FileWarning,
} from "lucide-react";
import { db } from "@/db";
import {
  employees,
  attendanceRecords,
  leaveRequests,
  departments,
} from "@/db/schema";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/common/page-header";
import { StatCard } from "@/components/common/stat-card";
import { ComingSoonCard } from "@/components/common/coming-soon-card";

export async function HrDashboard({ orgId }: { orgId: string }) {
  const todayStr = new Date().toISOString().slice(0, 10);

  const [{ totalEmployees }] = await db
    .select({ totalEmployees: count() })
    .from(employees)
    .where(
      and(eq(employees.orgId, orgId), eq(employees.employmentStatus, "active")),
    );

  const todayAttendance = await db
    .select()
    .from(attendanceRecords)
    .where(eq(attendanceRecords.date, todayStr));

  const ontime = todayAttendance.filter((r) => r.status === "ontime").length;
  const late = todayAttendance.filter((r) => r.status === "late").length;
  const onLeave = todayAttendance.filter((r) => r.status === "on_leave").length;
  const absent = Math.max(
    totalEmployees - ontime - late - onLeave,
    0,
  );

  const [{ pending }] = await db
    .select({ pending: count() })
    .from(leaveRequests)
    .where(eq(leaveRequests.status, "manager_approved"));

  const deptCounts = await db
    .select({
      departmentId: employees.departmentId,
      total: count(),
    })
    .from(employees)
    .where(
      and(eq(employees.orgId, orgId), eq(employees.employmentStatus, "active")),
    )
    .groupBy(employees.departmentId);

  const allDepts = await db
    .select()
    .from(departments)
    .where(eq(departments.orgId, orgId));

  const deptMap = new Map(allDepts.map((d) => [d.id, d.name]));

  return (
    <>
      <PageHeader
        title="HR overview"
        description="Today's headcount and pending decisions."
      />

      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <StatCard
          label="Active employees"
          value={totalEmployees}
          icon={Users}
          tone="default"
        />
        <StatCard label="On time" value={ontime} icon={Clock} tone="success" />
        <StatCard label="Late" value={late} icon={Clock} tone="warning" />
        <StatCard label="Absent" value={absent} icon={Clock} tone="danger" />
      </div>

      <div className="grid gap-4 lg:grid-cols-3 mb-6">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Users className="h-4 w-4 text-[var(--primary)]" />
              Headcount by department
            </CardTitle>
          </CardHeader>
          <CardContent>
            {deptCounts.length === 0 ? (
              <div className="text-sm text-[var(--muted-foreground)] py-4">
                No employees yet.
              </div>
            ) : (
              <ul className="space-y-2.5">
                {deptCounts.map((dc) => {
                  const name = dc.departmentId
                    ? deptMap.get(dc.departmentId)
                    : "Unassigned";
                  const pct = totalEmployees
                    ? Math.round((dc.total / totalEmployees) * 100)
                    : 0;
                  return (
                    <li key={dc.departmentId ?? "none"}>
                      <div className="flex items-center justify-between text-sm">
                        <span>{name ?? "—"}</span>
                        <span className="text-[var(--muted-foreground)]">
                          {dc.total} · {pct}%
                        </span>
                      </div>
                      <div className="h-2 rounded-full bg-[var(--muted)] mt-1 overflow-hidden">
                        <div
                          className="h-full bg-[var(--primary)]"
                          style={{ width: `${pct}%` }}
                        />
                      </div>
                    </li>
                  );
                })}
              </ul>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Inbox className="h-4 w-4 text-[var(--primary)]" />
              Awaiting HR
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-semibold">{pending}</div>
            <div className="text-xs text-[var(--muted-foreground)] mt-1 mb-4">
              Manager-approved requests pending final HR decision
            </div>
            <Button asChild className="w-full">
              <Link href="/leave/inbox">Review</Link>
            </Button>
          </CardContent>
        </Card>
      </div>

      <h2 className="text-sm uppercase tracking-wider text-[var(--muted-foreground)] mb-3">
        Coming soon
      </h2>
      <div className="grid gap-3 md:grid-cols-3">
        <ComingSoonCard
          icon={Banknote}
          label="Payroll"
          detail="Run monthly payroll and generate payslips."
        />
        <ComingSoonCard
          icon={AlertTriangle}
          label="Penalties"
          detail="Track tardiness penalties and deductions."
        />
        <ComingSoonCard
          icon={FileWarning}
          label="Document expiries"
          detail="Track passport, work permit, and contract expiries."
        />
      </div>
    </>
  );
}
