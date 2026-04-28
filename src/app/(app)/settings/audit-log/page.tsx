import { redirect } from "next/navigation";
import { desc } from "drizzle-orm";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { auditLogs } from "@/db/schema";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { PageHeader } from "@/components/common/page-header";
import { formatDateTimeDisplay } from "@/lib/utils";

export default async function AuditLogPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== "admin") redirect("/");

  const list = await db
    .select()
    .from(auditLogs)
    .orderBy(desc(auditLogs.createdAt))
    .limit(100);

  return (
    <>
      <PageHeader
        title="Audit log"
        description="Every write to people data is recorded."
      />
      <Card>
        <CardContent className="p-0">
          <table className="w-full text-sm">
            <thead className="bg-[var(--muted)] text-[var(--muted-foreground)] text-left">
              <tr>
                <th className="px-4 py-2 font-medium">When</th>
                <th className="px-4 py-2 font-medium">Action</th>
                <th className="px-4 py-2 font-medium">Entity</th>
                <th className="px-4 py-2 font-medium">User</th>
                <th className="px-4 py-2 font-medium">IP</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {list.length === 0 ? (
                <tr>
                  <td
                    colSpan={5}
                    className="py-10 text-center text-[var(--muted-foreground)]"
                  >
                    No activity yet.
                  </td>
                </tr>
              ) : (
                list.map((row) => (
                  <tr key={row.id}>
                    <td className="px-4 py-2 text-xs text-[var(--muted-foreground)]">
                      {formatDateTimeDisplay(row.createdAt)}
                    </td>
                    <td className="px-4 py-2">
                      <Badge variant="outline" className="capitalize">
                        {row.action}
                      </Badge>
                    </td>
                    <td className="px-4 py-2">
                      <span className="capitalize">{row.entityType}</span>
                      {row.entityId && (
                        <span className="text-[var(--muted-foreground)]">
                          {" · "}
                          {row.entityId.slice(0, 8)}
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-2 text-[var(--muted-foreground)]">
                      {row.userId ? row.userId.slice(0, 8) : "system"}
                    </td>
                    <td className="px-4 py-2 text-[var(--muted-foreground)] font-mono text-xs">
                      {row.ipAddress ?? "—"}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </>
  );
}
