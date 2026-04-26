import { redirect } from "next/navigation";
import { eq, isNull, and } from "drizzle-orm";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { employees } from "@/db/schema";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { PageHeader } from "@/components/page-header";

export default async function UsersPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== "admin") redirect("/");

  const list = await db
    .select()
    .from(employees)
    .where(
      and(eq(employees.orgId, session.user.orgId), isNull(employees.deletedAt)),
    );

  return (
    <>
      <PageHeader title="Users" description="Accounts and roles." />
      <Card>
        <CardContent className="p-0">
          <table className="w-full text-sm">
            <thead className="bg-[var(--muted)] text-[var(--muted-foreground)] text-left">
              <tr>
                <th className="px-4 py-2 font-medium">Name</th>
                <th className="px-4 py-2 font-medium">Email</th>
                <th className="px-4 py-2 font-medium">Role</th>
                <th className="px-4 py-2 font-medium">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {list.map((e) => (
                <tr key={e.id}>
                  <td className="px-4 py-2">
                    {e.firstName} {e.lastName}
                  </td>
                  <td className="px-4 py-2 text-[var(--muted-foreground)]">
                    {e.email}
                  </td>
                  <td className="px-4 py-2">
                    <Badge variant="outline" className="capitalize">
                      {e.role}
                    </Badge>
                  </td>
                  <td className="px-4 py-2">
                    <Badge
                      variant={
                        e.employmentStatus === "active" ? "success" : "muted"
                      }
                    >
                      {e.employmentStatus}
                    </Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </>
  );
}
