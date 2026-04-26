import { redirect } from "next/navigation";
import { eq } from "drizzle-orm";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { departments, positions, offices, employees } from "@/db/schema";
import { PageHeader } from "@/components/page-header";
import { EmployeeForm } from "../employee-form";

export const metadata = { title: "New employee · YZH-HR" };

export default async function NewEmployeePage() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== "admin" && session.user.role !== "hr") {
    redirect("/employees");
  }

  const [depts, pos, off, mgrs] = await Promise.all([
    db.select().from(departments).where(eq(departments.orgId, session.user.orgId)),
    db.select().from(positions).where(eq(positions.orgId, session.user.orgId)),
    db.select().from(offices).where(eq(offices.orgId, session.user.orgId)),
    db
      .select({
        id: employees.id,
        firstName: employees.firstName,
        lastName: employees.lastName,
      })
      .from(employees)
      .where(eq(employees.orgId, session.user.orgId)),
  ]);

  return (
    <>
      <PageHeader
        title="New employee"
        description="Create a new employee account. They'll receive their initial credentials from you."
      />
      <EmployeeForm
        mode="create"
        departments={depts}
        positions={pos}
        offices={off}
        managers={mgrs}
      />
    </>
  );
}
