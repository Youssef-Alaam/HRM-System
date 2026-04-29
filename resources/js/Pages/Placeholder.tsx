import { Head } from '@inertiajs/react';
import { Construction } from 'lucide-react';
import { ReactElement } from 'react';

import AppLayout from '@/Layouts/AppLayout';

interface PlaceholderProps {
    title: string;
    description: string;
    next?: string;
}

function Placeholder({ next }: PlaceholderProps) {
    return (
        <>
            <Head title="Placeholder" />
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
        </>
    );
}

Placeholder.layout = (page: ReactElement<PlaceholderProps>) => (
    <AppLayout
        header={
            <div>
                <h1 className="text-2xl font-semibold text-yzh-ink">{page.props.title}</h1>
                <p className="mt-1 text-sm text-yzh-slate">{page.props.description}</p>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Placeholder;
