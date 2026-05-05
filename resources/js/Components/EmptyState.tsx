import { LucideIcon } from 'lucide-react';
import { ReactNode } from 'react';

type Props = {
    icon?: LucideIcon;
    heading: string;
    description?: string;
    action?: ReactNode;
};

export default function EmptyState({ icon: Icon, heading, description, action }: Props) {
    return (
        <div className="flex flex-col items-center justify-center py-16 text-center">
            {Icon && (
                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-yzh-bone-soft">
                    <Icon className="h-6 w-6 text-yzh-slate" />
                </div>
            )}
            <p className="font-mono text-xs uppercase tracking-[0.24em] text-yzh-gold mb-2">Empty</p>
            <h3 className="text-base font-semibold text-yzh-ink">{heading}</h3>
            {description && (
                <p className="mt-1 max-w-xs text-sm text-yzh-slate">{description}</p>
            )}
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
