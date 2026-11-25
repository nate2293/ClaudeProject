import {
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
    Pagination as PaginationRoot,
} from '@/components/ui/pagination';
import type { Paginated } from '@/types/shop';

/**
 * Page navigation for a paginated Laravel resource collection. Wide screens list
 * page numbers (Laravel collapses long ranges into "…"); phones show
 * "Page 2 of 5" between the arrows.
 */
export function Pagination({ meta, links }: Pick<Paginated<unknown>, 'meta' | 'links'>) {
    if (meta.last_page <= 1) return null;

    // Laravel's first and last entries are "Previous" / "Next", rendered separately.
    const pages = meta.links.slice(1, -1);

    return (
        <PaginationRoot className="mt-14">
            <PaginationContent>
                <PaginationItem>
                    <PaginationPrevious href={links.prev} preserveScroll={false} />
                </PaginationItem>

                {pages.map((page, index) => (
                    <PaginationItem key={`${page.label}-${index}`} className="hidden sm:block">
                        {page.url ? (
                            <PaginationLink href={page.url} isActive={page.active} aria-label={`Page ${page.label}`}>
                                {page.label}
                            </PaginationLink>
                        ) : (
                            <PaginationEllipsis />
                        )}
                    </PaginationItem>
                ))}

                <PaginationItem className="text-muted-foreground px-3 text-sm tabular-nums sm:hidden" aria-current="page">
                    Page {meta.current_page} of {meta.last_page}
                </PaginationItem>

                <PaginationItem>
                    <PaginationNext href={links.next} preserveScroll={false} />
                </PaginationItem>
            </PaginationContent>
        </PaginationRoot>
    );
}
