import { cn } from '@/lib/utils';
import type { ColourOption } from '@/types/shop';

/** Small square cloth chips listing a product's colourways. */
export function ColourChips({ colours, className }: { colours: ColourOption[]; className?: string }) {
    return (
        <ul className={cn('flex items-center gap-1', className)} aria-label={`Available in ${colours.map((c) => c.name).join(', ')}`}>
            {colours.map((colour) => (
                <li key={colour.name} title={colour.name} className="size-3 border border-black/15" style={{ backgroundColor: colour.hex }} />
            ))}
        </ul>
    );
}
