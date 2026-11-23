import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

interface SectionHeadingProps {
    title: string;
    description?: string;
    action?: { label: string; href: string };
    className?: string;
}

export function SectionHeading({ title, description, action, className }: SectionHeadingProps) {
    return (
        <div className={cn('mb-10 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-8', className)}>
            <div className="max-w-2xl">
                <h2 className="font-display text-3xl leading-tight sm:text-4xl">{title}</h2>
                {description && <p className="text-muted-foreground mt-2 max-w-prose">{description}</p>}
            </div>

            {action && (
                <Link href={action.href} className="shrink-0 text-sm font-medium underline decoration-1 underline-offset-4 hover:decoration-2">
                    {action.label}
                </Link>
            )}
        </div>
    );
}
