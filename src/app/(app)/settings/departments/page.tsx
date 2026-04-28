import { redirect } from "next/navigation";
import { eq, isNull, and } from "drizzle-orm";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { departments, employees } from "@/db/schema";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { PageHeader } from "@/components/common/page-header";

export default async function DepartmentsPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== "admin") redirect("/");

  const list = await db
    .select()
    .from(departments)
    .where(
      and(
        eq(departments.orgId, session.user.orgId),
        isNull(departments.deletedAt),
      ),
    );

  const counts = await db
    .select({
      departmentId: employees.departmentId,
      total: employees.id,
    })
    .from(employees)
    .where(eq(employees.orgId, session.user.orgId));

  const countMap = new Map<string, number>();
  for (const c of counts) {
    if (!c.departmentId) continue;
    countMap.set(c.departmentId, (countMap.get(c.departmentId) ?? 0) + 1);
  }

  return (
    <>
      <PageHeader
        title="Departments"
        description="Organize your company structure."
      />
      <Card>
        <CardContent className="p-0">
          {list.length === 0 ? (
            <div className="py-10 text-center text-sm text-[var(--muted-foreground)]">
              No departments yet.
            </div>
          ) : (
            <ul className="divide-y divide-[var(--border)]">
              {list.map((d) => (
                <li
                  key={d.id}
                  className="px-4 py-3 flex items-center justify-between"
                >
                  <div>
                    <div className="font-medium">{d.name}</div>
                    {d.parentDepartmentId && (
                      <div className="text-xs text-[var(--muted-foreground)]">
                        Subdept of {d.parentDepartmentId.slice(0, 8)}
                      </div>
                    )}
                  </div>
                  <Badge variant="muted">
                    {countMap.get(d.id) ?? 0} people
                  </Badge>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
      <p className="text-xs text-[var(--muted-foreground)] mt-3">
        Create / edit / delete UI lands in the next session.
      </p>
    </>
  );
}
