import { usePage } from '@inertiajs/react';
import { ArrowDownToLine } from 'lucide-react';

type Resource = 'employees' | 'assets' | 'documents';

/**
 * Stamped-style CSV + XLSX export CTAs. Plain `<a href>` so the browser
 * handles the download (Inertia router would parse the response as JSON
 * and break). Hidden when the user lacks `exports.any`.
 */
export default function ExportButtons({
    resource,
    permission = 'exports.any',
}: {
    resource: Resource;
    permission?: string;
}) {
    const page = usePage();
    const auth = page.props.auth as { user?: { permissions?: string[] } | null } | undefined;
    const permissions = auth?.user?.permissions ?? [];
    if (!permissions.includes(permission)) return null;

    const base = `/exports/${resource}`;

    return (
        <div className="flex items-center gap-2">
            <a
                href={`${base}?format=csv`}
                className="group inline-flex min-h-11 items-center gap-2 border border-yzh-bone-soft px-3 py-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text transition-colors duration-150 ease-out hover:border-yzh-ink hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
            >
                <ArrowDownToLine className="h-3.5 w-3.5" aria-hidden="true" />
                CSV
            </a>
            <a
                href={base}
                className="group inline-flex min-h-11 items-center gap-2 border border-yzh-bone-soft px-3 py-2 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text transition-colors duration-150 ease-out hover:border-yzh-ink hover:text-yzh-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
            >
                <ArrowDownToLine className="h-3.5 w-3.5" aria-hidden="true" />
                XLSX
            </a>
        </div>
    );
}
