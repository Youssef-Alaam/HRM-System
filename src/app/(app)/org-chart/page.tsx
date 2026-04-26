import { eq, isNull, and } from "drizzle-orm";
import { redirect } from "next/navigation";
import Link from "next/link";
import { db } from "@/db";
import { employees } from "@/db/schema";
import { auth } from "@/lib/auth";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { PageHeader } from "@/components/page-header";
import { initials } from "@/lib/utils";

type EmployeeNode = {
  id: string;
  firstName: string;
  lastName: string;
  managerId: string | null;
  positionId: string | null;
  children: EmployeeNode[];
};

export default async function OrgChartPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");

  const list = await db
    .select({
      id: employees.id,
      firstName: employees.firstName,
      lastName: employees.lastName,
      managerId: employees.managerId,
      positionId: employees.positionId,
    })
    .from(employees)
    .where(
      and(
        eq(employees.orgId, session.user.orgId),
        eq(employees.employmentStatus, "active"),
        isNull(employees.deletedAt),
      ),
    );

  const byId = new Map<string, EmployeeNode>();
  for (const e of list) byId.set(e.id, { ...e, children: [] });
  const roots: EmployeeNode[] = [];
  for (const node of byId.values()) {
    if (node.managerId && byId.has(node.managerId)) {
      byId.get(node.managerId)!.children.push(node);
    } else {
      roots.push(node);
    }
  }

  return (
    <>
      <PageHeader
        title="Organization chart"
        description="Reporting hierarchy at YZH."
      />
      {roots.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center text-sm text-[var(--muted-foreground)]">
            No employees yet.
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-6">
          {roots.map((root) => (
            <OrgNode key={root.id} node={root} depth={0} />
          ))}
        </div>
      )}
    </>
  );
}

function OrgNode({ node, depth }: { node: EmployeeNode; depth: number }) {
  return (
    <div className="space-y-3" style={{ marginLeft: depth === 0 ? 0 : 24 }}>
      <Link
        href={`/employees/${node.id}`}
        className="flex items-center gap-3 rounded-md border border-[var(--border)] bg-[var(--card)] p-3 hover:border-[var(--primary)]"
      >
        <Avatar className="h-10 w-10">
          <AvatarFallback>
            {initials(`${node.firstName} ${node.lastName}`)}
          </AvatarFallback>
        </Avatar>
        <div>
          <div className="font-medium">
            {node.firstName} {node.lastName}
          </div>
          {node.children.length > 0 && (
            <div className="text-xs text-[var(--muted-foreground)]">
              {node.children.length} direct report
              {node.children.length === 1 ? "" : "s"}
            </div>
          )}
        </div>
      </Link>
      {node.children.length > 0 && (
        <div className="border-l-2 border-[var(--border)] pl-3 space-y-3">
          {node.children.map((child) => (
            <OrgNode key={child.id} node={child} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}
