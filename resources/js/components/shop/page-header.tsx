import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { type ReactNode } from 'react';
import { Container } from './container';

export interface Crumb {
    label: string;
    href?: string;
}

interface PageHeaderProps {
    title: string;
    description?: ReactNode;
    breadcrumbs?: Crumb[];
    children?: ReactNode;
}

/** Title band used at the top of inner storefront pages. */
export function PageHeader({ title, description, breadcrumbs, children }: PageHeaderProps) {
    return (
        <div className="border-b">
            <Container className="py-10 sm:py-14">
                {breadcrumbs && <Breadcrumbs items={breadcrumbs} />}
                <h1 className="font-display text-4xl leading-tight sm:text-5xl">{title}</h1>
                {description && <p className="text-muted-foreground mt-3 max-w-2xl text-base">{description}</p>}
                {children}
            </Container>
        </div>
    );
}

export function Breadcrumbs({ items }: { items: Crumb[] }) {
    return (
        <nav aria-label="Breadcrumb" className="mb-4">
            <ol className="text-muted-foreground flex flex-wrap items-center gap-1.5 text-sm">
                {items.map((item, index) => (
                    <li key={item.label} className="flex items-center gap-1.5">
                        {index > 0 && <ChevronRight className="size-3.5" aria-hidden />}
                        {item.href ? (
                            <Link href={item.href} className="hover:text-foreground transition-colors">
                                {item.label}
                            </Link>
                        ) : (
                            <span aria-current="page" className="text-foreground">
                                {item.label}
                            </span>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}
