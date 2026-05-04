import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import { ReactNode } from 'react';
import { Tree, TreeNode } from 'react-organizational-chart';

type OrgNode = {
    id: number;
    employee_code: string;
    first_name: string;
    last_name: string;
    position: string | null;
    department: string | null;
    children: OrgNode[];
};

type Props = {
    roots: OrgNode[];
};

function NodeCard({ node }: { node: OrgNode }) {
    return (
        <Link
            href={`/employees/${node.id}`}
            className="inline-flex min-w-[14rem] flex-col gap-1 border border-yzh-bone-soft bg-white px-4 py-3 text-left transition-colors duration-150 ease-out hover:border-yzh-ink hover:bg-yzh-bone-soft/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yzh-gold"
        >
            <span className="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-yzh-gold">
                {node.employee_code}
            </span>
            <span className="text-base font-semibold leading-tight tracking-tight text-yzh-ink">
                {node.first_name} {node.last_name}
            </span>
            {node.position && (
                <span className="text-sm text-yzh-slate">{node.position}</span>
            )}
            {node.department && (
                <span className="font-mono text-[0.6875rem] uppercase tracking-[0.18em] text-yzh-text">
                    {node.department}
                </span>
            )}
        </Link>
    );
}

function renderChildren(node: OrgNode): ReactNode {
    return node.children.map((child) => (
        <TreeNode key={child.id} label={<NodeCard node={child} />}>
            {renderChildren(child)}
        </TreeNode>
    ));
}

function Index({ roots }: Props) {
    return (
        <>
            <Head title="Org chart" />

            <div className="space-y-12 sm:space-y-16">
                <section>
                    <div className="border-t border-yzh-bone-soft pt-5">
                        <div className="flex items-baseline gap-3">
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                                00
                            </span>
                            <span className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-text">
                                Reporting tree / {roots.length}{' '}
                                {roots.length === 1 ? 'root' : 'roots'}
                            </span>
                        </div>

                        {roots.length === 0 ? (
                            <p className="mt-8 text-sm text-yzh-slate">
                                No employees yet. The reporting tree appears
                                once employees are added in the Employees
                                module.
                            </p>
                        ) : (
                            <div className="mt-10 overflow-x-auto pb-6">
                                <div className="inline-block min-w-full">
                                    {roots.map((root) => (
                                        <div
                                            key={root.id}
                                            className="mb-12 last:mb-0"
                                        >
                                            <Tree
                                                lineHeight="32px"
                                                lineWidth="1px"
                                                lineColor="rgb(212 209 198)"
                                                lineBorderRadius="2px"
                                                label={<NodeCard node={root} />}
                                            >
                                                {renderChildren(root)}
                                            </Tree>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </>
    );
}

Index.layout = (page: ReactNode) => (
    <AppLayout
        header={
            <div className="flex flex-col gap-2">
                <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold">
                    B.02 / People / Org chart
                </p>
                <h1 className="text-3xl font-semibold leading-tight tracking-tight text-yzh-ink sm:text-4xl">
                    Org chart.
                </h1>
            </div>
        }
    >
        {page}
    </AppLayout>
);

export default Index;
