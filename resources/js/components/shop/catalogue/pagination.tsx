import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

/** Page navigation for a paginated Laravel resource collection. */
export function Pagination({ meta, links }: Pick<Paginated<unknown>, 'meta' | 'links'>) {
    if (meta.last_page <= 1) return null;

    // Laravel's first and last entries are "Previous" / "Next"; render those with icons instead.
    const pages = meta.links.slice(1, -1);

    return (
        <nav aria-label="Pagination" className="mt-14 flex items-center justify-center gap-1">
            <PageLink href={links.prev} label="Previous page">
                <ChevronLeft />
            </PageLink>

            {pages.map((page, index) =>
                page.url ? (
                    <Link
                        key={`${page.label}-${index}`}
                        href={page.url}
                        preserveScroll={false}
                        aria-current={page.active ? 'page' : undefined}
                        className={cn(buttonVariants({ variant: page.active ? 'default' : 'ghost', size: 'icon' }), 'size-10')}
                    >
                        {page.label}
                    </Link>
                ) : (
                    <span key={`gap-${index}`} className="text-muted-foreground px-2">
                        …
                    </span>
                ),
            )}

            <PageLink href={links.next} label="Next page">
                <ChevronRight />
            </PageLink>
        </nav>
    );
}

function PageLink({ href, label, children }: { href: string | null; label: string; children: React.ReactNode }) {
    const className = cn(buttonVariants({ variant: 'ghost', size: 'icon' }), 'size-10');

    if (!href) {
        return (
            <span className={cn(className, 'pointer-events-none opacity-40')} aria-hidden>
                {children}
            </span>
        );
    }

    return (
        <Link href={href} className={className} aria-label={label}>
            {children}
        </Link>
    );
}
