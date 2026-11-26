import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { type ReactNode } from 'react';

export interface CtaFooterLink {
    label: string;
    href: string;
    /** Opens another site: a plain link in a new tab. */
    external?: boolean;
}

export interface CtaFooterColumn {
    title: string;
    links: CtaFooterLink[];
}

export interface CtaFooterProps {
    /** The call to action's heading. Pass null (with action null) to leave the band out. */
    title?: ReactNode;
    description?: ReactNode;
    action?: { label: string; href: string } | null;
    columns?: CtaFooterColumn[];
    /** Bottom bar: copyright and legal lines. */
    legal?: ReactNode;
    className?: string;
    /** Keeps content in line with the rest of the page (e.g. the site's Container classes). */
    innerClassName?: string;
}

const defaultColumns: CtaFooterColumn[] = [
    { title: 'Product', links: ['Features', 'Pricing', 'Changelog'].map((label) => ({ label, href: '#' })) },
    { title: 'Resources', links: ['Documentation', 'Blog', 'Guides'].map((label) => ({ label, href: '#' })) },
    { title: 'Company', links: ['About', 'Careers', 'Contact'].map((label) => ({ label, href: '#' })) },
];

const linkClass =
    'text-muted-foreground hover:text-foreground focus-visible:text-foreground focus-visible:ring-ring focus-visible:ring-offset-background inline-block rounded-sm py-1 text-sm transition-colors duration-150 outline-none focus-visible:ring-[3px] focus-visible:ring-offset-2';

/**
 * A footer led by a call to action (heading, a line of copy and a button) on a
 * tinted band, then columns of links and a bottom bar for the legal lines.
 */
export function CtaFooter({
    title = 'Ready to get started?',
    description = 'Free to start, no credit card required.',
    action = { label: 'Get started', href: '#' },
    columns = defaultColumns,
    legal = `© ${new Date().getFullYear()} Acme Inc. All rights reserved.`,
    className,
    innerClassName = 'mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8',
}: CtaFooterProps) {
    return (
        <footer className={cn('border-border bg-background border-t', className)}>
            {(title || action) && (
                <div className="border-border bg-muted/60 border-b">
                    <div className={cn('py-12 text-center sm:py-14', innerClassName)}>
                        {title && <h2 className="font-display text-2xl text-balance sm:text-3xl">{title}</h2>}
                        {description && <p className="text-muted-foreground mx-auto mt-2 max-w-md text-sm text-balance sm:text-base">{description}</p>}
                        {action && (
                            <Button asChild className="group mt-6">
                                <Link href={action.href}>
                                    {action.label}
                                    <ArrowRight
                                        aria-hidden
                                        className="transition-transform duration-150 group-hover:translate-x-0.5 group-focus-visible:translate-x-0.5 motion-reduce:transition-none"
                                    />
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>
            )}

            {columns.length > 0 && (
                <nav aria-label="Footer" className={cn('grid grid-cols-2 gap-8 py-10 sm:grid-cols-3', innerClassName)}>
                    {columns.map((column) => (
                        <div key={column.title}>
                            <h2 className="text-muted-foreground mb-2 text-sm">{column.title}</h2>
                            <ul className="space-y-0.5">
                                {column.links.map((link) => (
                                    <li key={link.href + link.label}>
                                        {link.external ? (
                                            <a href={link.href} target="_blank" rel="noopener noreferrer" className={linkClass}>
                                                {link.label}
                                                <span className="sr-only"> (opens in a new tab)</span>
                                            </a>
                                        ) : (
                                            <Link href={link.href} className={linkClass}>
                                                {link.label}
                                            </Link>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </nav>
            )}

            {legal && (
                <div className="border-border border-t">
                    <div className={cn('text-muted-foreground flex flex-col gap-1.5 py-5 text-xs sm:flex-row sm:flex-wrap sm:items-center sm:justify-between', innerClassName)}>
                        {legal}
                    </div>
                </div>
            )}
        </footer>
    );
}

export default CtaFooter;
