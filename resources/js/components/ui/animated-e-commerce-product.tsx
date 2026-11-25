import { contrastRatio, mix, readableInk } from '@/lib/colour';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Check, LoaderCircle, ShoppingBag, X } from 'lucide-react';
import { type KeyboardEvent, type ReactNode, useEffect, useId, useRef, useState } from 'react';

export interface Colourway {
    name: string;
    /** #rrggbb */
    hex: string;
    /** Photo of this colourway; falls back to the product `image`, then a woven swatch. */
    image?: string | null;
}

export interface AnimatedEcommerceProductProps {
    title: string;
    description?: string;
    /** Where the title and picture link to. */
    href?: string;
    colourways: Colourway[];
    /** Photo shared by every colourway (until per-colour photos exist). */
    image?: string | null;
    /** The formatted price (e.g. the shop's <Price />, which shows reductions). */
    price: ReactNode;
    /** Shown in the corner of the picture, e.g. a swing tag. */
    badge?: ReactNode;
    initialColour?: string;
    sizes?: string[];
    /** Whether a colour/size can be bought. Defaults to always. */
    isAvailable?: (colour: string, size: string) => boolean;
    /** Nothing can be bought: the button becomes "Sold out". */
    soldOut?: boolean;
    /** Add one unit to the bag. Reject with a message to show it under the button. */
    onAddToCart?: (choice: { colour: string; size: string }) => Promise<void> | void;
    addLabel?: string;
    className?: string;
}

const LIGHT_SURFACE = '#f2f2f2';
const DARK_SURFACE = '#0e1412';

const defaults: AnimatedEcommerceProductProps = {
    title: 'Merino Crew Neck Jumper',
    description: 'Knitwear',
    colourways: [
        { name: 'Oat', hex: '#d8cbb6' },
        { name: 'Navy', hex: '#28324a' },
        { name: 'Forest', hex: '#3f5446' },
    ],
    price: '£89',
    sizes: ['S', 'M', 'L'],
};

const gradientFor = (hex: string) => `linear-gradient(135deg, ${mix(hex, 0.35)} 40%, ${mix(hex, -0.3)} 100%)`;

/**
 * A product card whose colourways can be browsed in place: choosing a colour
 * crossfades the card's gradient to that colour and slides the colourway in
 * (a woven, pinked-edge swatch until there's a photo). "Add to bag" asks for a
 * size inside the card. Respects reduced motion, works by keyboard (arrow keys
 * between colours, Escape to close the size picker) and in light and dark themes.
 */
export function AnimatedEcommerceProduct(props: Partial<AnimatedEcommerceProductProps>) {
    const {
        title,
        description,
        href,
        colourways,
        image,
        price,
        badge,
        initialColour,
        sizes = [],
        isAvailable = () => true,
        soldOut = false,
        onAddToCart,
        addLabel = 'Add to bag',
        className,
    } = { ...defaults, ...props };

    const startIndex = Math.max(
        0,
        colourways.findIndex((c) => c.name === initialColour),
    );
    const [index, setIndex] = useState(startIndex);
    const [previous, setPrevious] = useState(startIndex);
    const [changed, setChanged] = useState(false);
    const [picking, setPicking] = useState(false);
    const [status, setStatus] = useState<'idle' | 'adding' | 'added'>('idle');
    const [error, setError] = useState<string>();
    const addButton = useRef<HTMLButtonElement>(null);
    const firstSize = useRef<HTMLButtonElement>(null);
    const dots = useRef<(HTMLButtonElement | null)[]>([]);
    const id = useId();

    const active = colourways[index] ?? colourways[0];
    const photo = active.image ?? image;
    const inkLight = readableInk(active.hex, LIGHT_SURFACE);
    const inkDark = readableInk(active.hex, DARK_SURFACE);
    const fillText = contrastRatio(mix(active.hex, -0.3), '#ffffff') >= 4.5 ? '#ffffff' : '#1a1a1a';
    const colourSoldOut = soldOut || (sizes.length > 0 && sizes.every((size) => !isAvailable(active.name, size)));

    useEffect(() => {
        if (picking) firstSize.current?.focus();
    }, [picking]);

    useEffect(() => {
        if (status !== 'added') return;
        const timer = setTimeout(() => setStatus('idle'), 2200);
        return () => clearTimeout(timer);
    }, [status]);

    function choose(next: number, focus = false) {
        if (next === index) return;
        setPrevious(index);
        setIndex(next);
        setChanged(true);
        setPicking(false);
        setError(undefined);
        if (focus) dots.current[next]?.focus();
    }

    function onDotKey(event: KeyboardEvent<HTMLButtonElement>) {
        const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];
        if (step === undefined) return;
        event.preventDefault();
        choose((index + step + colourways.length) % colourways.length, true);
    }

    async function add(size: string) {
        if (!onAddToCart) return;
        setStatus('adding');
        setError(undefined);
        try {
            await onAddToCart({ colour: active.name, size });
            setPicking(false);
            setStatus('added');
            addButton.current?.focus();
        } catch (reason) {
            setStatus('idle');
            setError(reason instanceof Error ? reason.message : String(reason ?? 'Couldn’t add this to your bag.'));
        }
    }

    function onAddClick() {
        if (sizes.length <= 1) {
            void add(sizes[0] ?? 'One size');
        } else {
            setPicking((open) => !open);
        }
    }

    function closePicker() {
        setPicking(false);
        addButton.current?.focus();
    }

    const picture = (
        <>
            {/* Previous gradient underneath, the new one fading in on top. */}
            <div aria-hidden className="absolute inset-0" style={{ background: gradientFor(colourways[previous]?.hex ?? active.hex) }} />
            <div
                key={`bg-${index}`}
                aria-hidden
                className={cn('absolute inset-0', changed && 'motion-safe:animate-[colourway-fade_700ms_ease_both]')}
                style={{ background: gradientFor(active.hex) }}
            />
            {photo ? (
                <img
                    key={`photo-${index}`}
                    src={photo}
                    alt={`${title} in ${active.name}`}
                    loading="lazy"
                    className={cn(
                        'rounded-tile absolute inset-[7%] h-[86%] w-[86%] object-cover shadow-[0_18px_30px_-12px_rgb(0_0_0/0.45)]',
                        changed && 'motion-safe:animate-[colourway-in_650ms_cubic-bezier(0.34,1.36,0.64,1)_both]',
                    )}
                />
            ) : (
                <div
                    key={`swatch-${index}`}
                    role="img"
                    aria-label={`${title} in ${active.name}`}
                    className={cn(
                        'weave pinked-top absolute inset-x-[16%] top-[15%] bottom-[15%] rotate-[-6deg] rounded-b-tile shadow-[0_22px_32px_-14px_rgb(0_0_0/0.5)]',
                        changed && 'motion-safe:animate-[colourway-in_650ms_cubic-bezier(0.34,1.36,0.64,1)_both]',
                    )}
                    style={{ backgroundColor: active.hex, ['--cw-rotate' as string]: '-6deg' }}
                />
            )}
        </>
    );

    return (
        <article
            className={cn('group text-foreground relative flex flex-col', className)}
            style={{ ['--cw-ink' as string]: inkLight, ['--cw-ink-dark' as string]: inkDark }}
        >
            <div className={cn('bg-muted rounded-card relative aspect-[3/4] overflow-hidden', soldOut && 'opacity-60')}>
                {href ? (
                    <Link href={href} tabIndex={-1} aria-hidden className="absolute inset-0">
                        {picture}
                    </Link>
                ) : (
                    picture
                )}
                {badge}
                {picking && (
                    <div
                        id={`${id}-sizes`}
                        role="group"
                        aria-label={`Choose a size for ${title} in ${active.name}`}
                        onKeyDown={(event) => event.key === 'Escape' && closePicker()}
                        className="bg-background/95 rounded-tile absolute inset-x-2 bottom-2 z-20 border p-2.5 shadow-lg backdrop-blur-sm motion-safe:animate-[colourway-fade_200ms_ease_both]"
                    >
                        <div className="mb-2 flex items-center justify-between">
                            <p className="text-xs font-medium">Choose a size</p>
                            <button
                                type="button"
                                onClick={closePicker}
                                aria-label="Close sizes"
                                className="text-muted-foreground hover:text-foreground focus-visible:ring-ring rounded-sm focus-visible:ring-2 focus-visible:outline-none"
                            >
                                <X className="size-4" aria-hidden />
                            </button>
                        </div>
                        <div className="grid grid-cols-[repeat(auto-fill,minmax(2.75rem,1fr))] gap-1.5">
                            {sizes.map((size, i) => {
                                const available = isAvailable(active.name, size);

                                return (
                                    <button
                                        key={size}
                                        ref={i === sizes.findIndex((s) => isAvailable(active.name, s)) ? firstSize : undefined}
                                        type="button"
                                        disabled={!available || status === 'adding'}
                                        onClick={() => add(size)}
                                        aria-label={available ? `Add size ${size}` : `Size ${size}, sold out`}
                                        className={cn(
                                            'h-9 rounded-full border text-xs font-medium transition-colors',
                                            'hover:border-(--cw-ink) dark:hover:border-(--cw-ink-dark)',
                                            'focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                                            'disabled:text-muted-foreground disabled:hover:border-border disabled:cursor-not-allowed disabled:border-dashed disabled:line-through',
                                        )}
                                    >
                                        {size}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>

            <div className="mt-3 flex flex-1 flex-col">
                <h3 className="text-[0.9375rem] leading-snug font-medium">
                    {href ? (
                        <Link href={href} className="decoration-1 underline-offset-4 hover:underline">
                            {title}
                        </Link>
                    ) : (
                        title
                    )}
                </h3>
                {description && <p className="text-muted-foreground text-sm">{description}</p>}

                {colourways.length > 1 && (
                    <div role="radiogroup" aria-label={`Colour: ${active.name}`} className="mt-2.5 -ml-1 flex flex-wrap items-center gap-0.5">
                        {colourways.map((colourway, i) => {
                            const selected = i === index;

                            return (
                                <button
                                    key={colourway.name}
                                    ref={(el) => {
                                        dots.current[i] = el;
                                    }}
                                    type="button"
                                    role="radio"
                                    aria-checked={selected}
                                    aria-label={colourway.name}
                                    title={colourway.name}
                                    tabIndex={selected ? 0 : -1}
                                    onClick={() => choose(i)}
                                    onKeyDown={onDotKey}
                                    className="focus-visible:ring-ring relative grid size-7 place-items-center rounded-full focus-visible:ring-2 focus-visible:outline-none"
                                >
                                    {/* The ring draws itself round the chosen colour. */}
                                    <svg aria-hidden viewBox="0 0 16 16" className="absolute inset-0 size-full -rotate-90">
                                        <circle
                                            cx="8"
                                            cy="8"
                                            r="6.2"
                                            fill="none"
                                            strokeWidth="1.1"
                                            strokeLinecap="round"
                                            strokeDasharray="39 39"
                                            strokeDashoffset={selected ? 0 : 39}
                                            className="stroke-(--cw-ink) transition-[stroke-dashoffset] duration-500 motion-reduce:transition-none dark:stroke-(--cw-ink-dark)"
                                        />
                                    </svg>
                                    <span
                                        className={cn(
                                            'size-3 rounded-full border border-black/15 transition-transform duration-300 dark:border-white/35',
                                            selected && 'scale-90',
                                        )}
                                        style={{ backgroundColor: colourway.hex }}
                                    />
                                </button>
                            );
                        })}
                    </div>
                )}

                <div className="mt-auto flex flex-wrap items-center justify-between gap-x-3 gap-y-2 pt-3">
                    <div>{price}</div>
                    {onAddToCart && (
                        <button
                            ref={addButton}
                            type="button"
                            onClick={onAddClick}
                            disabled={colourSoldOut || status === 'adding'}
                            aria-expanded={sizes.length > 1 ? picking : undefined}
                            aria-controls={sizes.length > 1 ? `${id}-sizes` : undefined}
                            className={cn(
                                'group/btn relative isolate inline-flex h-9 items-center gap-1.5 overflow-hidden rounded-md border px-4 text-xs font-semibold transition-[color,border-color,scale] duration-150 enabled:active:scale-[0.98] motion-reduce:transition-none [@media(pointer:coarse)]:min-h-11',
                                'border-(--cw-ink) text-(--cw-ink) dark:border-(--cw-ink-dark) dark:text-(--cw-ink-dark)',
                                'focus-visible:ring-ring focus-visible:ring-[3px] focus-visible:ring-offset-2 focus-visible:outline-none',
                                'enabled:hover:border-transparent enabled:hover:text-(--cw-fill-text) enabled:focus-visible:text-(--cw-fill-text)',
                                'disabled:border-border disabled:text-muted-foreground disabled:cursor-not-allowed',
                            )}
                            style={{ ['--cw-fill-text' as string]: fillText }}
                        >
                            <span
                                aria-hidden
                                className="absolute inset-0 -z-10 opacity-0 transition-opacity duration-150 group-hover/btn:opacity-100 group-focus-visible/btn:opacity-100 group-disabled/btn:opacity-0!"
                                style={{ background: `linear-gradient(135deg, ${active.hex}, ${mix(active.hex, -0.3)})` }}
                            />
                            <>
                            {status === 'adding' ? (
                                <LoaderCircle className="size-3.5 animate-spin" aria-hidden />
                            ) : status === 'added' ? (
                                <Check className="size-3.5" aria-hidden />
                            ) : (
                                <ShoppingBag className="size-3.5" aria-hidden />
                            )}
                            {colourSoldOut ? 'Sold out' : status === 'added' ? 'Added' : addLabel}
                            </>
                        </button>
                    )}
                </div>

                <p aria-live="polite" className={cn('text-brand text-xs', error ? 'mt-2' : 'sr-only')}>
                    {error ?? (status === 'added' ? `${title} in ${active.name} added to your bag.` : '')}
                </p>
            </div>
        </article>
    );
}

export default AnimatedEcommerceProduct;
