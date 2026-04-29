import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ReactElement } from 'react';

interface PlaceholderProps {
    title: string;
    description: string;
    next?: string;
}

function Placeholder({ title, description, next }: PlaceholderProps) {
    return (
        <>
            <Head title={title} />

            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                Status
                            </span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Coming soon
                            </span>
                        </div>
                        <h2 className="mt-3 text-2xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-3xl">
                            {title} ships in the Feature Phase.
                        </h2>
                        <p className="mt-3 max-w-xl text-sm leading-relaxed text-yzh-slate">
                            {description} The route, permission gate, sidebar
                            item, and audit trail for this page are already in
                            place. The screen itself is built one feature at a
                            time, with Walid's approval after each.
                        </p>
                        {next && (
                            <p className="mt-5 font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-text">
                                Next{' '}
                                <span className="ml-3 text-yzh-bone-soft">
                                    /
                                </span>{' '}
                                <span className="ml-3 text-yzh-slate">
                                    {next}
                                </span>
                            </p>
                        )}
                        <Link
                            href="/dashboard"
                            className="mt-8 inline-flex min-h-11 items-center gap-2 rounded font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-slate transition-colors duration-150 ease-out hover:text-yzh-gold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
                        >
                            <ArrowLeft
                                className="h-3.5 w-3.5"
                                aria-hidden="true"
                            />
                            Back to dashboard
                        </Link>
                    </div>
                </section>
            </div>
        </>
    );
}

Placeholder.layout = (page: ReactElement<PlaceholderProps>) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    Foundation / {page.props.title}
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    {page.props.title}.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Placeholder;
