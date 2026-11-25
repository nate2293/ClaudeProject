import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { type ReactNode } from 'react';

export interface FooterLink {
    label: string;
    href: string;
    /** Opens another site: a plain link in a new tab. */
    external?: boolean;
}

export interface FooterLinkColumn {
    title: string;
    links: FooterLink[];
}

export interface AnimatedLinksFooterProps {
    /** Top-left: the name or logo. */
    brand?: ReactNode;
    /** A line under the brand. */
    description?: ReactNode;
    columns?: FooterLinkColumn[];
    /** Bottom bar, left: e.g. the copyright line. */
    legal?: ReactNode;
    /** Bottom bar, right. */
    note?: ReactNode;
    className?: string;
    /** Keeps content in line with the rest of the page (e.g. the site's Container classes). */
    innerClassName?: string;
}

const defaultColumns: FooterLinkColumn[] = [
    { title: 'Product', links: ['Features', 'Pricing', 'Changelog'].map((label) => ({ label, href: '#' })) },
    { title: 'Resources', links: ['Documentation', 'Guides', 'Blog'].map((label) => ({ label, href: '#' })) },
    { title: 'Company', links: ['About', 'Careers', 'Contact'].map((label) => ({ label, href: '#' })) },
];

function AnimatedLink({ link }: { link: FooterLink }) {
    const className =
        'group text-muted-foreground hover:text-foreground focus-visible:text-foreground focus-visible:ring-ring inline-flex items-center rounded-sm py-1 text-sm transition-colors duration-200 outline-none focus-visible:ring-[3px] focus-visible:ring-offset-2 focus-visible:ring-offset-background';
    const content = (
        <>
            {/* The arrow grows in from nothing, nudging the label along. */}
            <span
                aria-hidden
                className="inline-flex w-0 -translate-x-1 overflow-hidden opacity-0 transition-[width,translate,opacity] duration-200 group-hover:w-4 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:w-4 group-focus-visible:translate-x-0 group-focus-visible:opacity-100 motion-reduce:transition-none"
            >
                <ArrowRight className="size-3 shrink-0" />
            </span>
            <span>{link.label}</span>
            {link.external && <span className="sr-only"> (opens in a new tab)</span>}
        </>
    );

    return link.external ? (
        <a href={link.href} target="_blank" rel="noopener noreferrer" className={className}>
            {content}
        </a>
    ) : (
        <Link href={link.href} className={className}>
            {content}
        </Link>
    );
}

/**
 * A footer in three bands: brand and a line about it, columns of links whose
 * arrow slides in on hover or keyboard focus, and a bottom bar for the legal
 * line. Light and dark themes; reduced-motion users get it without the slide.
 */
export function AnimatedLinksFooter({
    brand = <span className="text-sm font-medium">Acme Inc</span>,
    description = 'Modern tools for teams that ship fast.',
    columns = defaultColumns,
    legal = `© ${new Date().getFullYear()} Acme Inc. All rights reserved.`,
    note,
    className,
    innerClassName = 'mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8',
}: AnimatedLinksFooterProps) {
    return (
        <footer className={cn('border-border bg-background border-t', className)}>
            <div className={cn('border-border border-b py-8', innerClassName)}>
                {brand}
                {description && <p className="text-muted-foreground mt-2 max-w-sm text-sm text-balance">{description}</p>}
            </div>

            {columns.length > 0 && (
                <nav aria-label="Footer" className={cn('grid grid-cols-2 gap-8 py-8 sm:grid-cols-3', innerClassName)}>
                    {columns.map((column) => (
                        <div key={column.title}>
                            <h2 className="text-muted-foreground mb-3 text-sm">{column.title}</h2>
                            <ul className="space-y-1">
                                {column.links.map((link) => (
                                    <li key={link.href + link.label}>
                                        <AnimatedLink link={link} />
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </nav>
            )}

            {(legal || note) && (
                <div className="border-border border-t">
                    <div className={cn('text-muted-foreground flex flex-col gap-2 py-5 text-xs sm:flex-row sm:items-center sm:justify-between', innerClassName)}>
                        {legal && <p>{legal}</p>}
                        {note && <p>{note}</p>}
                    </div>
                </div>
            )}
        </footer>
    );
}

export default AnimatedLinksFooter;
