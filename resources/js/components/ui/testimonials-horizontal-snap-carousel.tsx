import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight, ChevronLeft, ChevronRight, Star } from 'lucide-react';
import { type KeyboardEvent, type ReactNode, useCallback, useEffect, useId, useRef, useState } from 'react';

export interface CarouselTestimonial {
    quote: string;
    author: string;
    /** e.g. a town, or "Role · Company". */
    detail?: string;
    /** 1–5. */
    rating?: number;
}

export interface TestimonialsHorizontalSnapCarouselProps {
    eyebrow?: string;
    title?: ReactNode;
    description?: ReactNode;
    items?: CarouselTestimonial[];
    primaryCta?: { label: string; href: string };
    secondaryCta?: { label: string; href: string };
    className?: string;
}

const defaults = {
    eyebrow: 'Customer stories',
    title: 'Worn in, and still loved.',
    description: 'Swipe, drag or use the arrows to read what customers say after a few seasons of wear.',
    items: [
        { quote: 'The jumper is the softest thing I own. Three winters in and it still looks new.', author: 'Hannah R.', detail: 'Bristol', rating: 5 },
        { quote: 'Sizing is consistent. I ordered two shirts and both fitted perfectly.', author: 'James T.', detail: 'Manchester', rating: 5 },
        { quote: 'Beautiful packaging and quick delivery. The coat is better in person.', author: 'Priya S.', detail: 'Edinburgh', rating: 4 },
    ] as CarouselTestimonial[],
};

function initials(name: string) {
    return name
        .split(/\s+/)
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function Stars({ rating }: { rating: number }) {
    return (
        <div role="img" aria-label={`${rating} out of 5 stars`} className="flex items-center gap-0.5">
            {Array.from({ length: 5 }, (_, i) => (
                <Star key={i} aria-hidden className={cn('size-3.5', i < rating ? 'fill-foreground text-foreground' : 'text-border')} />
            ))}
        </div>
    );
}

/**
 * Testimonials in a horizontal row that snaps card by card: swipe or drag on
 * touch screens, the arrow buttons (disabled at either end) or ←/→ once the
 * row has focus. Edges fade into the page background. Smooth scrolling is off
 * for reduced-motion users.
 */
export function TestimonialsHorizontalSnapCarousel(props: TestimonialsHorizontalSnapCarouselProps) {
    const { eyebrow, title, description, items, primaryCta, secondaryCta, className } = { ...defaults, ...props };
    const scroller = useRef<HTMLDivElement>(null);
    const [canPrev, setCanPrev] = useState(false);
    const [canNext, setCanNext] = useState(false);
    const headingId = useId();

    const update = useCallback(() => {
        const el = scroller.current;
        if (!el) return;
        setCanPrev(el.scrollLeft > 4);
        setCanNext(el.scrollLeft + el.clientWidth < el.scrollWidth - 4);
    }, []);

    useEffect(() => {
        const el = scroller.current;
        if (!el) return;
        update();
        el.addEventListener('scroll', update, { passive: true });
        const observer = new ResizeObserver(update);
        observer.observe(el);
        return () => {
            el.removeEventListener('scroll', update);
            observer.disconnect();
        };
    }, [update]);

    const step = (direction: 1 | -1) => {
        const el = scroller.current;
        const card = el?.querySelector('figure');
        if (!el || !card) return;
        const gap = parseFloat(getComputedStyle(el).columnGap) || 0;
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        el.scrollBy({ left: direction * (card.getBoundingClientRect().width + gap), behavior: reduced ? 'auto' : 'smooth' });
    };

    const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            step(event.key === 'ArrowRight' ? 1 : -1);
        }
    };

    return (
        <section aria-labelledby={headingId} className={cn('mx-auto w-full max-w-7xl', className)}>
            <div className="mx-auto max-w-2xl px-4 text-center">
                {eyebrow && <p className="text-muted-foreground text-sm font-medium">{eyebrow}</p>}
                <h2 id={headingId} className="font-display mt-3 text-3xl leading-tight sm:text-4xl lg:text-5xl">
                    {title}
                </h2>
                {description && <p className="text-muted-foreground mx-auto mt-4 max-w-xl text-base leading-relaxed">{description}</p>}
            </div>

            <div className="relative mt-12">
                <div
                    ref={scroller}
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="Customer testimonials"
                    tabIndex={0}
                    onKeyDown={onKeyDown}
                    className="focus-visible:ring-ring rounded-card flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-px-4 px-4 pb-2 [scrollbar-width:none] focus-visible:ring-2 focus-visible:outline-none sm:scroll-px-16 sm:px-16 [&::-webkit-scrollbar]:hidden"
                >
                    {items.map((item, index) => (
                        <figure
                            key={item.author + index}
                            role="group"
                            aria-roledescription="slide"
                            aria-label={`${index + 1} of ${items.length}`}
                            className="bg-card rounded-card flex w-[min(20rem,calc(100vw-4rem))] shrink-0 snap-start flex-col border p-6 sm:w-[22.5rem]"
                        >
                            {item.rating !== undefined && <Stars rating={item.rating} />}
                            <blockquote className="mt-4 flex-1 text-base leading-relaxed">“{item.quote}”</blockquote>
                            <figcaption className="mt-6 flex items-center gap-3">
                                <span
                                    aria-hidden
                                    className="bg-secondary text-secondary-foreground grid size-10 shrink-0 place-items-center rounded-full text-xs font-semibold"
                                >
                                    {initials(item.author)}
                                </span>
                                <span className="min-w-0">
                                    <span className="block truncate text-sm font-semibold">{item.author}</span>
                                    {item.detail && <span className="text-muted-foreground block truncate text-xs">{item.detail}</span>}
                                </span>
                            </figcaption>
                        </figure>
                    ))}
                </div>
                <div aria-hidden className="from-background pointer-events-none absolute inset-y-0 left-0 w-6 bg-gradient-to-r to-transparent sm:w-16" />
                <div aria-hidden className="from-background pointer-events-none absolute inset-y-0 right-0 w-6 bg-gradient-to-l to-transparent sm:w-16" />
            </div>

            <div className="mt-6 flex items-center justify-center gap-2">
                <Button variant="outline" size="icon" onClick={() => step(-1)} disabled={!canPrev} aria-label="Previous testimonials" aria-controls={undefined}>
                    <ChevronLeft />
                </Button>
                <Button variant="outline" size="icon" onClick={() => step(1)} disabled={!canNext} aria-label="Next testimonials">
                    <ChevronRight />
                </Button>
            </div>

            {(primaryCta || secondaryCta) && (
                <div className="mt-10 flex flex-col items-center gap-3 px-4 sm:flex-row sm:justify-center">
                    {primaryCta && (
                        <Button size="lg" asChild className="group h-12 w-full text-base sm:w-auto sm:min-w-56">
                            <Link href={primaryCta.href}>
                                {primaryCta.label}
                                <ArrowRight
                                    aria-hidden
                                    className="-translate-x-1 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100"
                                />
                            </Link>
                        </Button>
                    )}
                    {secondaryCta && (
                        <Button size="lg" variant="ghost" asChild className="h-12 w-full text-base sm:w-auto sm:min-w-56">
                            <Link href={secondaryCta.href}>{secondaryCta.label}</Link>
                        </Button>
                    )}
                </div>
            )}
        </section>
    );
}

export default TestimonialsHorizontalSnapCarousel;
