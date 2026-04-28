import { redirect, notFound } from "next/navigation";
import Link from "next/link";
import { eq } from "drizzle-orm";
import { Pencil, Mail, Phone, MapPin, Calendar, Briefcase } from "lucide-react";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { employees, departments, positions, offices } from "@/db/schema";
import { canEditEmployee, canViewEmployee } from "@/lib/rbac";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { PageHeader } from "@/components/common/page-header";
import {
  formatDateDisplay,
  initials,
  piastersToEgp,
} from "@/lib/utils";

export default async function EmployeeDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const session = await auth();
  if (!session?.user) redirect("/login");

  const { id: rawId } = await params;
  const id =
    rawId === "me" ? session.user.employeeId : rawId;

  const employee = await db.query.employees.findFirst({
    where: eq(employees.id, id),
  });
  if (!employee) notFound();

  const actor = {
    id: session.user.id,
    employeeId: session.user.employeeId,
    role: session.user.role,
    orgId: session.user.orgId,
    firstName: session.user.firstName,
    lastName: session.user.lastName,
    email: session.user.email ?? "",
  };
  if (!canViewEmployee(actor, employee)) {
    return (
      <Card>
        <CardContent className="py-10 text-center">
          <p className="text-sm text-[var(--muted-foreground)]">
            You don&apos;t have access to this employee.
          </p>
          <Button asChild variant="outline" size="sm" className="mt-3">
            <Link href="/">Back to dashboard</Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  const [dept, pos, office, manager] = await Promise.all([
    employee.departmentId
      ? db.query.departments.findFirst({
          where: eq(departments.id, employee.departmentId),
        })
      : null,
    employee.positionId
      ? db.query.positions.findFirst({
          where: eq(positions.id, employee.positionId),
        })
      : null,
    employee.officeId
      ? db.query.offices.findFirst({
          where: eq(offices.id, employee.officeId),
        })
      : null,
    employee.managerId
      ? db.query.employees.findFirst({
          where: eq(employees.id, employee.managerId),
        })
      : null,
  ]);

  const editable = canEditEmployee(actor, employee);
  const isSelf = actor.employeeId === employee.id;

  return (
    <>
      <PageHeader
        title={`${employee.firstName} ${employee.lastName}`}
        description={pos?.title ?? "—"}
        actions={
          editable ? (
            <Button asChild>
              <Link href={`/employees/${employee.id}/edit`}>
                <Pencil className="h-4 w-4 mr-1.5" /> Edit
              </Link>
            </Button>
          ) : null
        }
      />

      <div className="grid gap-4 lg:grid-cols-3 mb-6">
        <Card className="lg:col-span-1">
          <CardContent className="pt-6 flex flex-col items-center text-center">
            <Avatar className="h-24 w-24">
              <AvatarFallback className="text-2xl">
                {initials(`${employee.firstName} ${employee.lastName}`)}
              </AvatarFallback>
            </Avatar>
            <h2 className="mt-4 text-lg font-semibold">
              {employee.firstName} {employee.lastName}
            </h2>
            <div className="text-sm text-[var(--muted-foreground)]">
              {pos?.title ?? "—"}
            </div>
            <Badge
              variant={
                employee.employmentStatus === "active"
                  ? "success"
                  : employee.employmentStatus === "suspended"
                    ? "warning"
                    : "muted"
              }
              className="mt-2"
            >
              {employee.employmentStatus}
            </Badge>
            <div className="mt-4 w-full text-sm space-y-2">
              <Row icon={<Mail className="h-4 w-4" />} label={employee.email} />
              {employee.phone && (
                <Row
                  icon={<Phone className="h-4 w-4" />}
                  label={employee.phone}
                />
              )}
              {office && (
                <Row
                  icon={<MapPin className="h-4 w-4" />}
                  label={office.name}
                />
              )}
              <Row
                icon={<Briefcase className="h-4 w-4" />}
                label={`${employee.employeeCode} · ${employee.role}`}
              />
              {employee.hiringDate && (
                <Row
                  icon={<Calendar className="h-4 w-4" />}
                  label={`Hired ${formatDateDisplay(employee.hiringDate)}`}
                />
              )}
            </div>
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Profile</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-3 sm:grid-cols-2 text-sm">
            <DetailRow label="Department" value={dept?.name ?? "—"} />
            <DetailRow label="Manager" value={manager ? `${manager.firstName} ${manager.lastName}` : "—"} />
            <DetailRow label="National ID" value={employee.nationalId ?? "—"} />
            <DetailRow
              label="Date of birth"
              value={employee.dateOfBirth ? formatDateDisplay(employee.dateOfBirth) : "—"}
            />
            <DetailRow label="Gender" value={employee.gender ?? "—"} />
            <DetailRow label="Marital status" value={employee.maritalStatus ?? "—"} />
            <DetailRow label="Nationality" value={employee.nationality ?? "—"} />
            <DetailRow label="Address" value={employee.address ?? "—"} className="sm:col-span-2" />
            <DetailRow
              label="Emergency contact"
              value={
                employee.emergencyContactName
                  ? `${employee.emergencyContactName} · ${employee.emergencyContactPhone ?? ""}`
                  : "—"
              }
              className="sm:col-span-2"
            />
            <DetailRow
              label="Contract"
              value={
                employee.contractType
                  ? `${employee.contractType.replace("_", " ")}${
                      employee.contractStartDate
                        ? ` · from ${formatDateDisplay(employee.contractStartDate)}`
                        : ""
                    }${
                      employee.contractEndDate
                        ? ` to ${formatDateDisplay(employee.contractEndDate)}`
                        : ""
                    }`
                  : "—"
              }
              className="sm:col-span-2"
            />
            {(actor.role === "admin" || actor.role === "hr" || isSelf) && (
              <DetailRow
                label="Base salary"
                value={piastersToEgp(employee.baseSalaryPiasters)}
              />
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <BalanceCard
          label="Annual leave"
          value={`${employee.annualLeaveBalanceDays} days`}
        />
        <BalanceCard
          label="Sick leave"
          value={`${employee.sickLeaveBalanceDays} days`}
        />
        <BalanceCard
          label="Casual leave"
          value={`${employee.casualLeaveBalanceDays} days`}
        />
        <BalanceCard
          label="Permissions"
          value={`${employee.permissionsBalanceMinutes} min`}
        />
      </div>
    </>
  );
}

function Row({ icon, label }: { icon: React.ReactNode; label: string }) {
  return (
    <div className="flex items-center gap-2 justify-center text-[var(--muted-foreground)]">
      {icon}
      <span className="truncate">{label}</span>
    </div>
  );
}

function DetailRow({
  label,
  value,
  className,
}: {
  label: string;
  value: string;
  className?: string;
}) {
  return (
    <div className={className}>
      <div className="text-xs uppercase tracking-wider text-[var(--muted-foreground)]">
        {label}
      </div>
      <div className="mt-0.5">{value}</div>
    </div>
  );
}

function BalanceCard({ label, value }: { label: string; value: string }) {
  return (
    <Card>
      <CardContent className="p-4">
        <div className="text-xs text-[var(--muted-foreground)]">{label}</div>
        <div className="text-xl font-semibold mt-1">{value}</div>
      </CardContent>
    </Card>
  );
}
