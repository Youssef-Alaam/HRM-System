import { Head } from '@inertiajs/react';
import { Construction } from 'lucide-react';

import AppLayout from '@/Layouts/AppLayout';

interface PlaceholderProps {
    title: string;
    description: string;
    next?: string;
}

export default function Placeholder({ title, description, next }: PlaceholderProps) {
    return (
        <AppLayout
            header={
                <div>
                    <h1 className="text-2xl font-semibold text-yzh-ink">{title}</h1>
                    <p className="mt-1 text-sm text-yzh-slate">{description}</p>
                </div>
            }
        >
            <Head title={title} />
            <div className="mx-auto max-w-2xl">
                <div className="rounded-lg border border-dashed border-yzh-bone-soft bg-white p-10 text-center">
                    <Construction className="mx-auto h-10 w-10 text-yzh-gold" />
                    <h2 className="mt-4 text-lg font-medium text-yzh-ink">Coming soon</h2>
                    <p className="mt-2 text-sm text-yzh-slate">
                        This section is part of the foundation walkthrough. The page wiring,
                        routing, and permissions are in place; the feature itself ships in the
                        Feature Phase.
                    </p>
                    {next && (
                        <p className="mt-4 text-xs uppercase tracking-wider text-yzh-text">
                            Next: {next}
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
