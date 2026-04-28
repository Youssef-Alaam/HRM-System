import { Card, CardContent } from "@/components/ui/card";
import { PageHeader } from "@/components/common/page-header";

export function StubPage({
  title,
  description,
  bullets,
}: {
  title: string;
  description: string;
  bullets?: string[];
}) {
  return (
    <>
      <PageHeader title={title} description={description} />
      <Card>
        <CardContent className="py-12 max-w-2xl mx-auto text-sm text-[var(--muted-foreground)]">
          <p>
            This page is scaffolded but the full UI lands in the next session.
            The data model and Server Actions for it are already in place.
          </p>
          {bullets && bullets.length > 0 && (
            <ul className="mt-4 space-y-2 list-disc pl-5">
              {bullets.map((b) => (
                <li key={b}>{b}</li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </>
  );
}
