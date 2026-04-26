import { redirect } from "next/navigation";
import { eq, isNull, and } from "drizzle-orm";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { positions, departments } from "@/db/schema";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { PageHeader } from "@/components/page-header";

export default async function PositionsPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== "admin") redirect("/");

  const list = await db
    .select()
    .from(positions)
    .where(
      and(
        eq(positions.orgId, session.user.orgId),
        isNull(positions.deletedAt),
      ),
    );

  const depts = await db
    .select()
    .from(departments)
    .where(eq(departments.orgId, session.user.orgId));
  const deptMap = new Map(depts.map((d) => [d.id, d.name]));

  return (
    <>
      <PageHeader title="Positions" description="Job titles and levels." />
      <Card>
        <CardContent className="p-0">
          {list.length === 0 ? (
            <div className="py-10 text-center text-sm text-[var(--muted-foreground)]">
              No positions yet.
            </div>
          ) : (
            <ul className="divide-y divide-[var(--border)]">
              {list.map((p) => (
                <li
                  key={p.id}
                  className="px-4 py-3 flex items-center justify-between"
                >
                  <div>
                    <div className="font-medium">{p.title}</div>
                    <div className="text-xs text-[var(--muted-foreground)]">
                      {p.departmentId ? deptMap.get(p.departmentId) : "—"}
                    </div>
                  </div>
                  {p.level && <Badge variant="outline">{p.level}</Badge>}
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </>
  );
}
