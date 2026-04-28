import Link from "next/link";
import { Sparkles } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { PageHeader } from "@/components/common/page-header";

const COPY: Record<string, { title: string; tag: string; description: string }> = {
  payroll: {
    title: "Payroll",
    tag: "Coming in v2",
    description:
      "Full Egyptian-compliant payroll: monthly runs, payslip PDFs, NOSI/ETA exports, and bank file generation.",
  },
  assets: {
    title: "Assets management",
    tag: "Coming in v2",
    description:
      "Track laptops, phones, and equipment. Assign to employees, track history, and handle returns.",
  },
  performance: {
    title: "Performance reviews",
    tag: "Future",
    description:
      "Reviews, goals, OKRs, and 1-on-1s. Continuous feedback built into the flow.",
  },
  ai: {
    title: "AI assistant",
    tag: "Future",
    description:
      "Ask HR questions in plain language. Surface insights from attendance, leave, and performance trends.",
  },
  reports: {
    title: "Reports & exports",
    tag: "Coming in v2",
    description:
      "CSV/Excel exports for attendance, leave, payroll, and headcount. Customizable filters.",
  },
};

export default async function ComingSoonPage({
  params,
}: {
  params: Promise<{ module: string }>;
}) {
  const { module: moduleName } = await params;
  const copy =
    COPY[moduleName] ?? {
      title: moduleName.replace("-", " "),
      tag: "Coming Soon",
      description: "This module isn't available in the prototype yet.",
    };

  return (
    <>
      <PageHeader title={copy.title} />
      <Card>
        <CardContent className="py-16 flex flex-col items-center text-center max-w-lg mx-auto">
          <div className="h-16 w-16 rounded-full bg-[var(--accent)] text-[var(--accent-foreground)] flex items-center justify-center mb-4">
            <Sparkles className="h-7 w-7" />
          </div>
          <span className="text-[10px] uppercase tracking-wider rounded-full bg-[var(--muted)] text-[var(--muted-foreground)] px-2 py-0.5 mb-3">
            {copy.tag}
          </span>
          <h2 className="text-xl font-semibold capitalize">{copy.title}</h2>
          <p className="text-sm text-[var(--muted-foreground)] mt-2">
            {copy.description}
          </p>
          <Button asChild variant="outline" className="mt-6">
            <Link href="/">Back to dashboard</Link>
          </Button>
        </CardContent>
      </Card>
    </>
  );
}
