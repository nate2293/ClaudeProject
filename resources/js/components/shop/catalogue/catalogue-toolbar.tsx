import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { CatalogueFilters } from '@/types/shop';
import { router } from '@inertiajs/react';
import { X } from 'lucide-react';

const sortOptions: { value: CatalogueFilters['sort']; label: string }[] = [
    { value: 'featured', label: 'Featured' },
    { value: 'newest', label: 'Newest' },
    { value: 'price-asc', label: 'Price: low to high' },
    { value: 'price-desc', label: 'Price: high to low' },
];

interface CatalogueToolbarProps {
    /** URL of the listing being filtered (all products or a category). */
    baseUrl: string;
    filters: CatalogueFilters;
    types: string[];
    total: number;
}

/** Serialise filters into a clean query string, dropping defaults and empty values. */
function toQuery(filters: CatalogueFilters): Record<string, string | number> {
    const query: Record<string, string | number> = {};

    if (filters.q) query.q = filters.q;
    if (filters.type) query.type = filters.type;
    if (filters.sale) query.sale = 1;
    if (filters.sort !== 'featured') query.sort = filters.sort;

    return query;
}

export function CatalogueToolbar({ baseUrl, filters, types, total }: CatalogueToolbarProps) {
    function apply(changes: Partial<CatalogueFilters>) {
        // Changing any filter resets pagination by omitting `page`.
        router.get(baseUrl, toQuery({ ...filters, ...changes }), { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <div className="mb-8 flex flex-col gap-4 border-b pb-6 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex flex-wrap items-center gap-2" role="group" aria-label="Filter products">
                <FilterPill active={!filters.type} onClick={() => apply({ type: null })}>
                    Everything
                </FilterPill>
                {types.map((type) => (
                    <FilterPill key={type} active={filters.type === type} onClick={() => apply({ type })}>
                        {type}
                    </FilterPill>
                ))}

                <span className="bg-border mx-1 hidden h-5 w-px sm:block" aria-hidden />

                <FilterPill active={filters.sale} onClick={() => apply({ sale: !filters.sale })}>
                    Reduced only
                </FilterPill>

                {filters.q && (
                    <button
                        type="button"
                        onClick={() => apply({ q: null })}
                        className="bg-muted hover:bg-accent inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm"
                    >
                        Search: {filters.q}
                        <X className="size-3.5" aria-label="Clear search" />
                    </button>
                )}
            </div>

            <div className="flex items-center justify-between gap-4 lg:justify-end">
                <p className="text-muted-foreground text-sm" aria-live="polite">
                    {total} {total === 1 ? 'product' : 'products'}
                </p>

                <Select value={filters.sort} onValueChange={(value) => apply({ sort: value as CatalogueFilters['sort'] })}>
                    <SelectTrigger className="w-48" aria-label="Sort products">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent align="end">
                        {sortOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </div>
    );
}

/** A toggle button; `aria-pressed` tells assistive technology which filters are on. */
function FilterPill({ active, className, ...props }: React.ButtonHTMLAttributes<HTMLButtonElement> & { active: boolean }) {
    return (
        <button
            type="button"
            aria-pressed={active}
            className={cn(
                'rounded-md border px-3 py-1.5 text-sm font-medium transition-colors',
                active ? 'bg-foreground text-background border-foreground' : 'hover:border-foreground/40 bg-background',
                className,
            )}
            {...props}
        />
    );
}
