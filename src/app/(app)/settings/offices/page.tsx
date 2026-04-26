import { redirect } from "next/navigation";
import { eq, isNull, and } from "drizzle-orm";
import { MapPin } from "lucide-react";
import { auth } from "@/lib/auth";
import { db } from "@/db";
import { offices } from "@/db/schema";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { PageHeader } from "@/components/page-header";

export default async function OfficesPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  if (session.user.role !== "admin") redirect("/");

  const list = await db
    .select()
    .from(offices)
    .where(
      and(eq(offices.orgId, session.user.orgId), isNull(offices.deletedAt)),
    );

  return (
    <>
      <PageHeader
        title="Offices"
        description="Locations with check-in geofence radius."
      />
      <div className="grid gap-3 sm:grid-cols-2">
        {list.length === 0 ? (
          <Card className="sm:col-span-2">
            <CardContent className="py-10 text-center text-sm text-[var(--muted-foreground)]">
              No offices yet.
            </CardContent>
          </Card>
        ) : (
          list.map((o) => (
            <Card key={o.id}>
              <CardContent className="p-4">
                <div className="flex items-start justify-between">
                  <div>
                    <div className="font-medium">{o.name}</div>
                    <div className="text-xs text-[var(--muted-foreground)] mt-1 flex items-center gap-1">
                      <MapPin className="h-3 w-3" />
                      {o.city ?? "—"}
                    </div>
                  </div>
                  <Badge variant="muted">
                    {o.allowedCheckInRadiusMeters ?? "—"}m radius
                  </Badge>
                </div>
                {o.address && (
                  <div className="text-xs text-[var(--muted-foreground)] mt-3">
                    {o.address}
                  </div>
                )}
                {o.latitude && o.longitude && (
                  <div className="text-[11px] text-[var(--muted-foreground)] mt-2 font-mono">
                    {o.latitude}, {o.longitude}
                  </div>
                )}
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </>
  );
}
