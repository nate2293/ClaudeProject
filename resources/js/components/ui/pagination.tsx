import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-react';
import { type ComponentProps, type KeyboardEvent } from 'react';

/*
 * Composable, accessible pagination:
 *
 *   <Pagination>
 *     <PaginationContent>
 *       <PaginationItem><PaginationPrevious href="?page=1" /></PaginationItem>
 *       <PaginationItem><PaginationLink href="?page=1">1</PaginationLink></PaginationItem>
 *       <PaginationItem><PaginationLink href="?page=2" isActive>2</PaginationLink></PaginationItem>
 *       <PaginationItem><PaginationEllipsis /></PaginationItem>
 *       <PaginationItem><PaginationNext href="?page=3" /></PaginationItem>
 *     </PaginationContent>
 *   </Pagination>
 *
 * Links are Inertia links (in-app navigation). Leave `href` out of Previous/Next
 * on the first/last page and they render disabled, not as dead links.
 */

/** The landmark: a <nav> labelled "Pagination". */
export function Pagination({ className, ...props }: ComponentProps<'nav'>) {
    return <nav aria-label="Pagination" className={cn('mx-auto flex w-full justify-center', className)} {...props} />;
}

export function PaginationContent({ className, ...props }: ComponentProps<'ul'>) {
    return <ul className={cn('flex flex-row items-center gap-1', className)} {...props} />;
}

export function PaginationItem(props: ComponentProps<'li'>) {
    return <li {...props} />;
}

export type PaginationLinkProps = {
    isActive?: boolean;
    sizeValue?: 'default' | 'sm' | 'lg' | 'icon';
    /** Without an href the link renders disabled. */
    href?: string | null;
} & Omit<ComponentProps<typeof Link>, 'href'>;

/** Links behave like buttons here, so Space activates them as well as Enter. */
function activateOnSpace(event: KeyboardEvent<HTMLElement>) {
    if (event.key === ' ') {
        event.preventDefault();
        event.currentTarget.click();
    }
}

export function PaginationLink({ isActive, sizeValue = 'icon', href, className, children, ...props }: PaginationLinkProps) {
    const classes = cn(
        buttonVariants({ variant: isActive ? 'default' : 'ghost', size: sizeValue }),
        sizeValue === 'icon' && 'size-10 tabular-nums',
        className,
    );

    if (!href) {
        return (
            <span aria-disabled="true" className={cn(classes, 'pointer-events-none opacity-40')}>
                {children}
            </span>
        );
    }

    return (
        <Link href={href} aria-current={isActive ? 'page' : undefined} onKeyDown={activateOnSpace} className={classes} {...props}>
            {children}
        </Link>
    );
}

export function PaginationPrevious({ className, ...props }: PaginationLinkProps) {
    return (
        <PaginationLink aria-label="Previous page" sizeValue="default" className={cn('gap-1 pl-2.5', className)} {...props}>
            <ChevronLeft className="size-4" aria-hidden />
            <span className="hidden sm:inline">Previous</span>
        </PaginationLink>
    );
}

export function PaginationNext({ className, ...props }: PaginationLinkProps) {
    return (
        <PaginationLink aria-label="Next page" sizeValue="default" className={cn('gap-1 pr-2.5', className)} {...props}>
            <span className="hidden sm:inline">Next</span>
            <ChevronRight className="size-4" aria-hidden />
        </PaginationLink>
    );
}

/** Stands in for collapsed page numbers. */
export function PaginationEllipsis({ className, ...props }: ComponentProps<'span'>) {
    return (
        <span aria-hidden className={cn('text-muted-foreground flex size-10 items-center justify-center', className)} {...props}>
            <MoreHorizontal className="size-4" />
            <span className="sr-only">More pages</span>
        </span>
    );
}
