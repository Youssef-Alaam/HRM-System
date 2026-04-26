import Link from "next/link";
import { Button } from "@/components/ui/button";

export default function NotFound() {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-[var(--background)] px-4 text-center">
      <div className="text-6xl font-bold text-[var(--primary)]">404</div>
      <h1 className="mt-4 text-xl font-semibold">Page not found</h1>
      <p className="mt-2 text-sm text-[var(--muted-foreground)] max-w-md">
        The page you&apos;re looking for doesn&apos;t exist or you don&apos;t have access.
      </p>
      <Button asChild className="mt-6">
        <Link href="/">Back to dashboard</Link>
      </Button>
    </div>
  );
}
