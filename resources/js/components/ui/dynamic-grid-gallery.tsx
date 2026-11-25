import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type CSSProperties, type KeyboardEvent, useRef, useState } from 'react';

export interface GalleryItem {
    id: string | number;
    title: string;
    /** A second line, e.g. a price or department. */
    subtitle?: string;
    /** Where the tile leads. Without it the tile is a plain figure. */
    href?: string;
    /** Photo. Without one, a woven cloth swatch in `swatch` is shown. */
    src?: string | null;
    /** Cloth colour for the swatch (and the tile's backdrop while a photo loads). */
    swatch?: string;
    alt?: string;
}

export interface DynamicGridGalleryProps {
    /** Six items for the 3×2 grid (extra items are ignored). */
    items?: GalleryItem[];
    /** Gap between tiles, in px. */
    gap?: number;
    /** How much the active row and column grow (1 = not at all). */
    expandFactor?: number;
    /** Corner rounding class for each tile. */
    rounded?: string;
    /** Accessible name for the gallery. */
    label?: string;
    className?: string;
}

const COLS = 3;
const ROWS = 2;

export const DEFAULT_GALLERY_ITEMS: GalleryItem[] = [
    { id: 1, title: 'Merino Crew Neck Jumper', subtitle: '£89', swatch: '#d8cbb6' },
    { id: 2, title: 'Straight Leg Selvedge Jeans', subtitle: '£110', swatch: '#28324a' },
    { id: 3, title: 'Waxed Cotton Field Jacket', subtitle: '£165', swatch: '#4b5139' },
    { id: 4, title: 'Relaxed Linen Shirt', subtitle: '£65', swatch: '#a6c0d8' },
    { id: 5, title: 'Wool Blend Overcoat', subtitle: '£185', swatch: '#b08a5f' },
    { id: 6, title: 'Cashmere Ribbed Scarf', subtitle: '£75', swatch: '#a8552f' },
];

/**
 * A 3×2 grid of pictures where the row and column under the pointer (or keyboard
 * focus) grow, so one piece takes the stage. Each tile's name and price fade in
 * when it's active. Keyboard: Tab between tiles, arrow keys move around the grid,
 * Escape settles it. Phones get a plain two-column grid (there's no hover there),
 * and the resizing is instant for reduced-motion users.
 */
export function DynamicGridGallery({
    items = DEFAULT_GALLERY_ITEMS,
    gap = 10,
    expandFactor = 1.95,
    rounded = 'rounded-tile',
    label = 'Featured pieces',
    className,
}: DynamicGridGalleryProps) {
    const [active, setActive] = useState<number | null>(null);
    const tiles = useRef<(HTMLElement | null)[]>([]);
    const shown = items.slice(0, COLS * ROWS);

    const template = (count: number, index: number | null) =>
        Array.from({ length: count }, (_, i) => (index === i ? `${expandFactor}fr` : index === null ? '1fr' : '0.9fr')).join(' ');

    const grid = {
        '--gallery-cols': template(COLS, active === null ? null : active % COLS),
        '--gallery-rows': template(ROWS, active === null ? null : Math.floor(active / COLS)),
        gap: `${gap}px`,
    } as CSSProperties;

    const onKeyDown = (event: KeyboardEvent<HTMLDivElement>, index: number) => {
        if (event.key === 'Escape') {
            setActive(null);
            (event.target as HTMLElement).blur();
            return;
        }
        const move = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: COLS, ArrowUp: -COLS }[event.key];
        if (move === undefined) return;
        const next = index + move;
        if (next < 0 || next >= shown.length) return;
        event.preventDefault();
        tiles.current[next]?.focus();
    };

    return (
        <div role="group" aria-label={label} className={cn('relative w-full select-none', className)} onMouseLeave={() => setActive(null)}>
            <div
                style={grid}
                className={cn(
                    'grid grid-cols-2 md:h-full md:grid-cols-[var(--gallery-cols)] md:grid-rows-[var(--gallery-rows)]',
                    'transition-[grid-template-columns,grid-template-rows] duration-500 ease-[cubic-bezier(0.25,1,0.5,1)] motion-reduce:transition-none',
                )}
            >
                {shown.map((item, index) => {
                    const isActive = active === index;
                    const content = (
                        <>
                            {item.src ? (
                                <img
                                    src={item.src}
                                    alt={item.alt ?? item.title}
                                    loading={index < COLS ? 'eager' : 'lazy'}
                                    className="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105 motion-reduce:transition-none"
                                />
                            ) : (
                                <div
                                    role="img"
                                    aria-label={item.alt ?? item.title}
                                    className="weave absolute inset-0 transition-transform duration-700 ease-out group-hover:scale-105 motion-reduce:transition-none"
                                    style={{ backgroundColor: item.swatch ?? '#988686' }}
                                />
                            )}
                            {/* Soft vignette, lifted from the active tile. */}
                            <div
                                aria-hidden
                                className={cn(
                                    'pointer-events-none absolute inset-0 bg-gradient-to-t from-black/45 via-black/5 to-black/10 transition-opacity duration-300',
                                    isActive ? 'opacity-100' : 'opacity-60',
                                )}
                            />
                            {/* Name and price: always on phones, on the active tile elsewhere. */}
                            <div
                                className={cn(
                                    'absolute inset-x-0 bottom-0 p-3 text-white transition-all duration-300 sm:p-4 motion-reduce:transition-none',
                                    isActive ? 'translate-y-0 opacity-100' : 'md:translate-y-2 md:opacity-0',
                                )}
                            >
                                <p className="text-sm leading-tight font-semibold drop-shadow sm:text-base">{item.title}</p>
                                {item.subtitle && <p className="text-xs text-white/85 drop-shadow sm:text-sm">{item.subtitle}</p>}
                            </div>
                        </>
                    );
                    const tileClass = cn(
                        'group relative block aspect-[4/5] overflow-hidden bg-muted md:aspect-auto md:h-full',
                        'focus-visible:ring-ring focus-visible:ring-offset-background focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                        rounded,
                    );
                    const ref = (el: HTMLElement | null) => {
                        tiles.current[index] = el;
                    };
                    const events = {
                        onMouseEnter: () => setActive(index),
                        onFocus: () => setActive(index),
                        onBlur: () => setActive((current) => (current === index ? null : current)),
                    };

                    return (
                        <div key={item.id} className="min-h-0 min-w-0" onKeyDown={(event) => onKeyDown(event, index)}>
                            {item.href ? (
                                <Link ref={ref} href={item.href} className={tileClass} aria-label={[item.title, item.subtitle].filter(Boolean).join(', ')} {...events}>
                                    {content}
                                </Link>
                            ) : (
                                <figure ref={ref} tabIndex={0} className={tileClass} {...events}>
                                    {content}
                                </figure>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export default DynamicGridGallery;
