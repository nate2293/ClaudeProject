import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
}

export function EmptyState({ icon: Icon, title, description, action }: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center px-4 py-20 text-center">
            <Icon className="text-muted-foreground mb-5 size-8" strokeWidth={1.25} aria-hidden />
            <h2 className="font-display text-2xl">{title}</h2>
            {description && <p className="text-muted-foreground mt-2 max-w-sm text-sm">{description}</p>}
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
