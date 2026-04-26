import { redirect } from "next/navigation";
import { eq, asc } from "drizzle-orm";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { holidays } from "@/db/schema";
import { Card, CardContent } from "@/components/ui/card";
import { PageHeader } from "@/components/page-header";
import { formatDateDisplay } from "@/lib/utils";

export default async function HolidaysPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== "admin" && session.user.role !== "hr") {
    redirect("/");
  }

  const list = await db
    .select()
    .from(holidays)
    .where(eq(holidays.orgId, session.user.orgId))
    .orderBy(asc(holidays.date));

  return (
    <>
      <PageHeader
        title="Public holidays"
        description="Holiday on weekday = day off; holiday on weekend = lost (per Decision 9)."
      />
      <Card>
        <CardContent className="p-0">
          {list.length === 0 ? (
            <div className="py-10 text-center text-sm text-[var(--muted-foreground)]">
              No holidays configured.
            </div>
          ) : (
            <ul className="divide-y divide-[var(--border)]">
              {list.map((h) => (
                <li
                  key={h.id}
                  className="px-4 py-3 flex items-center justify-between"
                >
                  <div>
                    <div className="font-medium">{h.name}</div>
                    <div className="text-xs text-[var(--muted-foreground)]">
                      {formatDateDisplay(h.date)}
                      {h.isRecurring && " · recurs annually"}
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </>
  );
}
