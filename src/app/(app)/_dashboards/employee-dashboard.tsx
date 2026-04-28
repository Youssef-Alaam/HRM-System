import { eq, desc, and } from "drizzle-orm";
import Link from "next/link";
import {
  CalendarClock,
  CalendarCheck,
  ClipboardList,
  FileText,
  Banknote,
} from "lucide-react";
import { db } from "@/db";
import { employees, attendanceRecords, leaveRequests } from "@/db/schema";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { PageHeader } from "@/components/common/page-header";
import { ComingSoonCard } from "@/components/common/coming-soon-card";
import { formatDateDisplay, formatTimeDisplay } from "@/lib/utils";

export async function EmployeeDashboard({
  employeeId,
}: {
  employeeId: string;
}) {
  const me = await db.query.employees.findFirst({
    where: eq(employees.id, employeeId),
  });
  if (!me) return null;

  const todayStr = new Date().toISOString().slice(0, 10);

  const [todayAttendance] = await db
    .select()
    .from(attendanceRecords)
    .where(
      and(
        eq(attendanceRecords.employeeId, employeeId),
        eq(attendanceRecords.date, todayStr),
      ),
    )
    .limit(1);

  const recentRequests = await db
    .select()
    .from(leaveRequests)
    .where(eq(leaveRequests.employeeId, employeeId))
    .orderBy(desc(leaveRequests.createdAt))
    .limit(5);

  const isCheckedIn = !!todayAttendance?.checkInTime && !todayAttendance?.checkOutTime;
  const isDoneForDay = !!todayAttendance?.checkOutTime;

  return (
    <>
      <PageHeader
        title={`Hello, ${me.firstName}`}
        description="Here's what's happening today."
      />

      <div className="grid gap-4 md:grid-cols-2 mb-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <CalendarClock className="h-4 w-4 text-[var(--primary)]" />
              Today&apos;s schedule
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-sm text-[var(--muted-foreground)]">
              {me.shiftStartTime} – {me.shiftEndTime}
            </div>
            <div className="mt-3">
              {isDoneForDay ? (
                <Badge variant="success">Done for the day</Badge>
              ) : isCheckedIn ? (
                <Badge variant="warning">Signed in</Badge>
              ) : (
                <Badge variant="muted">Not signed in</Badge>
              )}
              {todayAttendance?.checkInTime && (
                <div className="text-xs text-[var(--muted-foreground)] mt-2">
                  In at {formatTimeDisplay(todayAttendance.checkInTime)}
                  {todayAttendance.checkOutTime &&
                    ` · Out at ${formatTimeDisplay(todayAttendance.checkOutTime)}`}
                </div>
              )}
            </div>
            <Button asChild className="mt-4 w-full" variant="default">
              <Link href="/schedule">
                {isCheckedIn
                  ? "Sign out"
                  : isDoneForDay
                    ? "View schedule"
                    : "Sign in"}
              </Link>
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <ClipboardList className="h-4 w-4 text-[var(--primary)]" />
              Leave balances
            </CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-2 gap-3">
            <BalanceTile
              label="Annual"
              value={Number(me.annualLeaveBalanceDays)}
              unit="days"
            />
            <BalanceTile
              label="Sick"
              value={Number(me.sickLeaveBalanceDays)}
              unit="days"
            />
            <BalanceTile
              label="Casual"
              value={Number(me.casualLeaveBalanceDays)}
              unit="days"
            />
            <BalanceTile
              label="Permission"
              value={me.permissionsBalanceMinutes}
              unit="min"
            />
            <Button asChild variant="outline" className="col-span-2 mt-1">
              <Link href="/leave/new">Request leave</Link>
            </Button>
          </CardContent>
        </Card>
      </div>

      <Card className="mb-6">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <CalendarCheck className="h-4 w-4 text-[var(--primary)]" />
            Recent requests
          </CardTitle>
        </CardHeader>
        <CardContent>
          {recentRequests.length === 0 ? (
            <div className="text-sm text-[var(--muted-foreground)] py-4">
              No requests yet. Submit one from the Leave page.
            </div>
          ) : (
            <ul className="divide-y divide-[var(--border)]">
              {recentRequests.map((req) => (
                <li
                  key={req.id}
                  className="py-3 flex items-center justify-between"
                >
                  <div>
                    <div className="text-sm font-medium capitalize">
                      {req.leaveType.replace("_", " ")} leave
                    </div>
                    <div className="text-xs text-[var(--muted-foreground)]">
                      {formatDateDisplay(req.startDate)} →{" "}
                      {formatDateDisplay(req.endDate)} · {req.daysCount} days
                    </div>
                  </div>
                  <RequestStatusBadge status={req.status} />
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>

      <h2 className="text-sm uppercase tracking-wider text-[var(--muted-foreground)] mb-3">
        Coming soon
      </h2>
      <div className="grid gap-3 md:grid-cols-3">
        <ComingSoonCard
          icon={Banknote}
          label="Payslips"
          detail="View and download monthly payslips."
        />
        <ComingSoonCard
          icon={FileText}
          label="Documents"
          detail="Personal documents, contracts, certificates."
        />
        <ComingSoonCard
          label="Tasks"
          detail="Assigned tasks and onboarding checklists."
        />
      </div>
    </>
  );
}

function BalanceTile({
  label,
  value,
  unit,
}: {
  label: string;
  value: number;
  unit: string;
}) {
  return (
    <div className="rounded-md border border-[var(--border)] p-3">
      <div className="text-xs text-[var(--muted-foreground)]">{label}</div>
      <div className="text-xl font-semibold mt-0.5">
        {value}
        <span className="text-xs text-[var(--muted-foreground)] ml-1 font-normal">
          {unit}
        </span>
      </div>
    </div>
  );
}

function RequestStatusBadge({
  status,
}: {
  status: typeof leaveRequests.$inferSelect.status;
}) {
  switch (status) {
    case "pending":
      return <Badge variant="warning">Pending manager</Badge>;
    case "manager_approved":
      return <Badge variant="warning">Pending HR</Badge>;
    case "approved":
      return <Badge variant="success">Approved</Badge>;
    case "rejected":
      return <Badge variant="danger">Rejected</Badge>;
    case "cancelled":
      return <Badge variant="muted">Cancelled</Badge>;
  }
}
