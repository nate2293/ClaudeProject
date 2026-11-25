import { cn } from '@/lib/utils';
import { Pause, Play } from 'lucide-react';
import { type ReactNode, useState } from 'react';

export interface MarqueeItem {
    label: string;
    /** An icon before the label. */
    icon?: ReactNode;
    /** Or a colour dot before the label. */
    swatch?: string;
}

export interface LogosMetricOverDualRowLogosProps {
    /** Small line above the figure. */
    eyebrow?: string;
    /** The big figure, e.g. 12000 (formatted with thousands separators). */
    metric?: number | string;
    /** Shown after the figure in a lighter tone, e.g. "+". */
    metricSuffix?: string;
    description?: ReactNode;
    /** Short facts under the description. */
    facts?: string[];
    /** Scrolls left. */
    topRow?: MarqueeItem[];
    /** Scrolls right. */
    bottomRow?: MarqueeItem[];
    topRowLabel?: string;
    bottomRowLabel?: string;
    /** Seconds for one full loop of each row. */
    topDuration?: number;
    bottomDuration?: number;
    /** Accessible name of the section. */
    label?: string;
    className?: string;
}

const demoRow = (names: string[]): MarqueeItem[] => names.map((label) => ({ label }));

function Row({ items, label, direction, duration, paused }: { items: MarqueeItem[]; label: string; direction: 'left' | 'right'; duration: number; paused: boolean }) {
    const list = (copy: boolean) => (
        <ul
            aria-label={copy ? undefined : label}
            aria-hidden={copy || undefined}
            className={cn(
                'flex shrink-0 items-center gap-10 pr-10',
                // Without motion, show one copy, wrapped and centred.
                copy && 'motion-reduce:hidden',
                'motion-reduce:flex-wrap motion-reduce:justify-center motion-reduce:gap-x-8 motion-reduce:gap-y-3 motion-reduce:pr-0',
            )}
        >
            {items.map((item, i) => (
                <li key={`${item.label}-${i}`} className="text-muted-foreground hover:text-foreground flex items-center gap-2.5 transition-colors duration-150">
                    {item.icon && (
                        <span aria-hidden className="[&_svg]:size-5 [&_svg]:shrink-0">
                            {item.icon}
                        </span>
                    )}
                    {!item.icon && item.swatch && (
                        <span aria-hidden className="size-3 shrink-0 rounded-full ring-1 ring-black/10 ring-inset dark:ring-white/15" style={{ backgroundColor: item.swatch }} />
                    )}
                    <span className="text-sm font-semibold whitespace-nowrap">{item.label}</span>
                </li>
            ))}
        </ul>
    );

    return (
        <div
            className={cn(
                'flex w-max motion-reduce:w-full motion-reduce:animate-none! group-hover/marquee:[animation-play-state:paused]',
                direction === 'left' ? 'animate-marquee-left' : 'animate-marquee-right',
                paused && '[animation-play-state:paused]!',
            )}
            style={{ animationDuration: `${duration}s` }}
        >
            {list(false)}
            {list(true)}
        </div>
    );
}

/**
 * A big figure with a short explanation, over two rows of names scrolling in
 * opposite directions with soft faded edges. The rows pause on hover and with
 * the pause button (WCAG 2.2.2); with reduced motion they sit still, wrapped.
 * Screen readers get each row once, as a list.
 */
export function LogosMetricOverDualRowLogos({
    eyebrow = 'By the numbers',
    metric = 12000,
    metricSuffix = '+',
    description = 'teams ship with us every day.',
    facts = [],
    topRow = demoRow(['Halide', 'Northwind', 'Ion', 'LaunchPad', 'Loam', 'Strata']),
    bottomRow = demoRow(['Tidal', 'Zephyr', 'Solaris', 'Lunar', 'Atlas', 'Facet']),
    topRowLabel = 'Top row',
    bottomRowLabel = 'Bottom row',
    topDuration = 40,
    bottomDuration = 50,
    label = 'Figures and names',
    className,
}: LogosMetricOverDualRowLogosProps) {
    const [paused, setPaused] = useState(false);
    const figure = typeof metric === 'number' ? new Intl.NumberFormat('en-GB').format(metric) : metric;

    return (
        <section aria-label={label} className={cn('py-12 sm:py-16', className)}>
            <div className="mx-auto max-w-2xl px-4 text-center">
                {eyebrow && <p className="text-muted-foreground text-sm">{eyebrow}</p>}
                <p className="font-logo text-foreground mt-3 text-6xl leading-[1.05] tabular-nums sm:text-7xl lg:text-8xl">
                    {figure}
                    {metricSuffix && <span className="text-foreground/55">{metricSuffix}</span>}
                </p>
                {description && <p className="text-muted-foreground mx-auto mt-4 max-w-xl text-base leading-relaxed text-pretty sm:text-lg">{description}</p>}
                {facts.length > 0 && (
                    <ul className="text-muted-foreground mt-5 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
                        {facts.map((fact) => (
                            <li key={fact} className="flex items-center gap-2">
                                <span aria-hidden className="bg-primary size-1.5 rounded-full" />
                                {fact}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <div className="group/marquee relative mt-12 space-y-6 overflow-hidden">
                {/* Soft edges. */}
                <div aria-hidden className="from-background pointer-events-none absolute inset-y-0 left-0 z-10 w-16 bg-gradient-to-r to-transparent motion-reduce:hidden sm:w-40" />
                <div aria-hidden className="from-background pointer-events-none absolute inset-y-0 right-0 z-10 w-16 bg-gradient-to-l to-transparent motion-reduce:hidden sm:w-40" />

                {topRow.length > 0 && <Row items={topRow} label={topRowLabel} direction="left" duration={topDuration} paused={paused} />}
                {bottomRow.length > 0 && <Row items={bottomRow} label={bottomRowLabel} direction="right" duration={bottomDuration} paused={paused} />}
            </div>

            <div className="mt-6 flex justify-center motion-reduce:hidden">
                <button
                    type="button"
                    onClick={() => setPaused((p) => !p)}
                    aria-pressed={paused}
                    className="text-muted-foreground hover:text-foreground focus-visible:ring-ring inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs transition-colors outline-none focus-visible:ring-[3px] [@media(pointer:coarse)]:min-h-11"
                >
                    {paused ? <Play aria-hidden className="size-3.5" /> : <Pause aria-hidden className="size-3.5" />}
                    {paused ? 'Play scrolling' : 'Pause scrolling'}
                </button>
            </div>
        </section>
    );
}

export default LogosMetricOverDualRowLogos;
