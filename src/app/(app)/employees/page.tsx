import Link from "next/link";
import { eq, and, isNull, or, like } from "drizzle-orm";
import { Plus, Search } from "lucide-react";
import { db } from "@/db";
import { employees, departments, positions } from "@/db/schema";
import { auth } from "@/lib/auth";
import { redirect } from "next/navigation";
import { PageHeader } from "@/components/common/page-header";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { initials, formatDateDisplay } from "@/lib/utils";

const PAGE_SIZE = 20;

export default async function EmployeesListPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; department?: string }>;
}) {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role === "employee") redirect("/");

  const sp = await searchParams;
  const q = sp.q?.trim() ?? "";
  const departmentFilter = sp.department ?? "";

  const conditions = [
    eq(employees.orgId, session.user.orgId),
    isNull(employees.deletedAt),
  ];

  if (session.user.role === "manager") {
    conditions.push(eq(employees.managerId, session.user.employeeId));
  }
  if (q) {
    const term = `%${q}%`;
    const orClause = or(
      like(employees.firstName, term),
      like(employees.lastName, term),
      like(employees.email, term),
      like(employees.employeeCode, term),
    );
    if (orClause) conditions.push(orClause);
  }
  if (departmentFilter) {
    conditions.push(eq(employees.departmentId, departmentFilter));
  }

  const list = await db
    .select({
      id: employees.id,
      firstName: employees.firstName,
      lastName: employees.lastName,
      email: employees.email,
      employeeCode: employees.employeeCode,
      employmentStatus: employees.employmentStatus,
      hiringDate: employees.hiringDate,
      departmentId: employees.departmentId,
      positionId: employees.positionId,
      photoUrl: employees.photoUrl,
    })
    .from(employees)
    .where(and(...conditions))
    .limit(PAGE_SIZE);

  const departmentRows = await db
    .select()
    .from(departments)
    .where(eq(departments.orgId, session.user.orgId));

  const positionRows = await db
    .select()
    .from(positions)
    .where(eq(positions.orgId, session.user.orgId));

  const deptMap = new Map(departmentRows.map((d) => [d.id, d.name]));
  const posMap = new Map(positionRows.map((p) => [p.id, p.title]));

  const canCreate =
    session.user.role === "admin" || session.user.role === "hr";

  return (
    <>
      <PageHeader
        title="Employees"
        description={
          session.user.role === "manager"
            ? "Your direct reports."
            : "All people at YZH."
        }
        actions={
          canCreate ? (
            <Button asChild>
              <Link href="/employees/new" className="flex items-center gap-1.5">
                <Plus className="h-4 w-4" /> New employee
              </Link>
            </Button>
          ) : null
        }
      />

      <form className="mb-4 flex flex-wrap gap-2" action="/employees" method="get">
        <div className="relative flex-1 min-w-[240px]">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[var(--muted-foreground)]" />
          <Input
            name="q"
            placeholder="Search by name, email, or code"
            defaultValue={q}
            className="pl-9"
          />
        </div>
        <select
          name="department"
          defaultValue={departmentFilter}
          className="h-9 rounded-md border border-[var(--input)] bg-[var(--background)] px-3 text-sm shadow-sm"
        >
          <option value="">All departments</option>
          {departmentRows.map((d) => (
            <option key={d.id} value={d.id}>
              {d.name}
            </option>
          ))}
        </select>
        <Button type="submit" variant="outline">
          Filter
        </Button>
      </form>

      {list.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center text-sm text-[var(--muted-foreground)]">
            No employees match your search.
            {canCreate && (
              <div className="mt-3">
                <Button asChild variant="outline" size="sm">
                  <Link href="/employees/new">Add your first employee</Link>
                </Button>
              </div>
            )}
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {list.map((e) => (
            <Link
              key={e.id}
              href={`/employees/${e.id}`}
              className="group rounded-lg border border-[var(--border)] bg-[var(--card)] p-4 hover:border-[var(--primary)] transition-colors"
            >
              <div className="flex items-start gap-3">
                <Avatar>
                  <AvatarFallback>
                    {initials(`${e.firstName} ${e.lastName}`)}
                  </AvatarFallback>
                </Avatar>
                <div className="flex-1 min-w-0">
                  <div className="font-medium group-hover:text-[var(--primary)] truncate">
                    {e.firstName} {e.lastName}
                  </div>
                  <div className="text-xs text-[var(--muted-foreground)] truncate">
                    {e.positionId
                      ? (posMap.get(e.positionId) ?? "—")
                      : "—"}
                  </div>
                  <div className="text-xs text-[var(--muted-foreground)] truncate">
                    {e.departmentId
                      ? (deptMap.get(e.departmentId) ?? "—")
                      : "—"}
                  </div>
                </div>
                <Badge
                  variant={
                    e.employmentStatus === "active"
                      ? "success"
                      : e.employmentStatus === "suspended"
                        ? "warning"
                        : "muted"
                  }
                >
                  {e.employmentStatus}
                </Badge>
              </div>
              <div className="mt-3 pt-3 border-t border-[var(--border)] flex items-center justify-between text-xs text-[var(--muted-foreground)]">
                <span>{e.employeeCode}</span>
                <span>Hired {formatDateDisplay(e.hiringDate)}</span>
              </div>
            </Link>
          ))}
        </div>
      )}

      {list.length === PAGE_SIZE && (
        <p className="text-xs text-[var(--muted-foreground)] mt-4">
          Showing first {PAGE_SIZE}. Pagination coming in a follow-up pass.
        </p>
      )}
    </>
  );
}
