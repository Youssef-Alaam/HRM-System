import { redirect, notFound } from "next/navigation";
import { eq } from "drizzle-orm";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { employees, departments, positions, offices } from "@/db/schema";
import { canEditEmployee } from "@/lib/rbac";
import { PageHeader } from "@/components/page-header";
import { EmployeeForm } from "../../employee-form";

export default async function EditEmployeePage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const session = await auth();
  if (!session?.user) redirect("/login");

  const { id } = await params;
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
  if (!canEditEmployee(actor, employee)) redirect(`/employees/${id}`);

  const [depts, pos, off, mgrs] = await Promise.all([
    db.select().from(departments).where(eq(departments.orgId, actor.orgId)),
    db.select().from(positions).where(eq(positions.orgId, actor.orgId)),
    db.select().from(offices).where(eq(offices.orgId, actor.orgId)),
    db
      .select({
        id: employees.id,
        firstName: employees.firstName,
        lastName: employees.lastName,
      })
      .from(employees)
      .where(eq(employees.orgId, actor.orgId)),
  ]);

  return (
    <>
      <PageHeader
        title={`Edit ${employee.firstName} ${employee.lastName}`}
        description="Update employment details and contact info."
      />
      <EmployeeForm
        mode="edit"
        initial={{
          id: employee.id,
          firstName: employee.firstName,
          lastName: employee.lastName,
          email: employee.email,
          phone: employee.phone ?? "",
          nationalId: employee.nationalId ?? "",
          dateOfBirth: employee.dateOfBirth ? String(employee.dateOfBirth) : "",
          gender: employee.gender ?? "",
          maritalStatus: employee.maritalStatus ?? "",
          nationality: employee.nationality ?? "",
          address: employee.address ?? "",
          emergencyContactName: employee.emergencyContactName ?? "",
          emergencyContactPhone: employee.emergencyContactPhone ?? "",
          employeeCode: employee.employeeCode,
          positionId: employee.positionId ?? "",
          departmentId: employee.departmentId ?? "",
          officeId: employee.officeId ?? "",
          managerId: employee.managerId ?? "",
          hiringDate: employee.hiringDate ? String(employee.hiringDate) : "",
          contractType: employee.contractType ?? "",
          contractStartDate: employee.contractStartDate
            ? String(employee.contractStartDate)
            : "",
          contractEndDate: employee.contractEndDate
            ? String(employee.contractEndDate)
            : "",
          employmentStatus: employee.employmentStatus,
          baseSalaryEgp: String(employee.baseSalaryPiasters / 100),
          role: employee.role,
        }}
        departments={depts}
        positions={pos}
        offices={off}
        managers={mgrs}
      />
    </>
  );
}
