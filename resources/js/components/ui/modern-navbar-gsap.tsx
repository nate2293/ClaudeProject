import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type MouseEvent, useCallback, useId, useLayoutEffect, useRef } from 'react';

export interface ModernNavItem {
    label: string;
    href: string;
    /** The page this item leads to is the one being viewed. */
    active?: boolean;
    /** "sale" draws the label in a red that reads on the dark capsule. */
    tone?: 'default' | 'sale';
}

export interface ModernNavbarGsapProps {
    items?: ModernNavItem[];
    /** Accessible name of the navigation landmark. */
    label?: string;
    className?: string;
}

const defaultItems: ModernNavItem[] = [
    { label: 'Home', href: '/', active: true },
    { label: 'Shop', href: '/shop' },
    { label: 'About', href: '/about' },
    { label: 'Contact', href: '/contact' },
];

/** The underline bar's width in px (matches its w-7 class); the beam animates from its centre. */
const BAR_WIDTH = 28;
/** Total length of the strike, as in the original GSAP timeline. */
const DURATION = 600;

/**
 * Remembers where the underline was when a link was clicked. Each page renders
 * its own header, so the next header reads this and plays the strike from the
 * old item to the new one as the page appears.
 */
let pendingStrike: { from: number; at: number } | null = null;

const LIGHTNING =
    'M113.5 6.5L109.068 8.9621C109.023 8.98721 108.974 9.00516 108.923 9.01531L106.889 9.42219C106.661 9.46776 106.432 9.35034 106.336 9.1388L104.045 4.0986C104.015 4.03362 104 3.96307 104 3.8917V2.12268C104 1.6898 103.487 1.46145 103.166 1.75103L99.2887 5.24019C99.1188 5.39305 98.867 5.41132 98.6768 5.28457L95.0699 2.87996C94.7881 2.69205 94.4049 2.83291 94.3118 3.15862L92.6148 9.09827C92.5483 9.33084 92.3249 9.48249 92.0843 9.45843L87.7087 9.02087C87.5752 9.00752 87.4419 9.04839 87.3389 9.13428L84.9485 11.1263C84.7128 11.3227 84.3575 11.2625 84.1996 10.9994L81.7602 6.93359C81.617 6.69492 81.3064 6.61913 81.0694 6.76501L75.3165 10.3052C75.1286 10.4209 74.8871 10.3997 74.7223 10.2531L70.6678 6.64917C70.5611 6.55429 70.5 6.41829 70.5 6.27547V1.20711C70.5 1.0745 70.4473 0.947322 70.3536 0.853553L70.2185 0.718508C70.0846 0.584592 69.8865 0.537831 69.7068 0.59772L69.2675 0.744166C68.9149 0.861705 68.8092 1.30924 69.0721 1.57206L69.605 2.10499C69.8157 2.31571 69.7965 2.66281 69.5638 2.84897L67.5 4.5L65.2715 6.28282C65.1083 6.41338 64.8811 6.42866 64.7019 6.32113L60.3621 3.71725C60.153 3.59179 59.8839 3.63546 59.7252 3.8206L57.0401 6.95327C57.0135 6.9843 56.9908 7.01849 56.9725 7.05505L55.2533 10.4934C55.1188 10.7624 54.779 10.8526 54.5287 10.6858L50.7686 8.17907C50.6051 8.07006 50.3929 8.06694 50.2263 8.17109L46.7094 10.3691C46.5774 10.4516 46.4145 10.468 46.2688 10.4133L42.6586 9.05949C42.5558 9.02091 42.4684 8.94951 42.4102 8.85633L40.1248 5.1997C40.0458 5.07323 40.0273 4.91808 40.0745 4.77659L40.6374 3.08777C40.7755 2.67359 40.3536 2.29381 39.9562 2.47447L35.5 4.5L32.2657 5.88613C32.1013 5.95658 31.9118 5.93386 31.7687 5.82656L30.1904 4.64279C30.0699 4.55245 29.9152 4.5212 29.7691 4.55772L26.2009 5.44977C26.0723 5.48193 25.9617 5.56388 25.8934 5.67759L23.1949 10.1752C23.0796 10.3673 22.8507 10.4593 22.6346 10.4003L17.6887 9.05148C17.5674 9.01838 17.463 8.94076 17.3963 8.83409L15.3331 5.53299C15.1627 5.26032 14.7829 5.21707 14.5556 5.44443L12.1464 7.85355C12.0527 7.94732 11.9255 8 11.7929 8H8.15139C8.05268 8 7.95617 7.97078 7.87404 7.91603L3.74143 5.16095C3.59214 5.06142 3.40096 5.04952 3.24047 5.12976L0.5 6.5';

const easeOut = (t: number) => 1 - (1 - t) * (1 - t);
const clamp = (t: number) => Math.min(1, Math.max(0, t));

/**
 * A capsule navigation whose underline strikes across like a beam of light to
 * the newly chosen item: it stretches out, crackles, then collapses into place.
 * Real links with aria-current on the current page; reduced-motion users get a
 * plain move. Light and dark themes.
 */
export function ModernNavbarGsap({ items = defaultItems, label = 'Main', className }: ModernNavbarGsapProps) {
    const navRef = useRef<HTMLElement>(null);
    const barRef = useRef<HTMLDivElement>(null);
    const linkRefs = useRef<(HTMLAnchorElement | null)[]>([]);
    const frame = useRef(0);
    const gradientId = useId().replace(/:/g, '');
    const activeIndex = items.findIndex((item) => item.active);

    const xFor = useCallback((index: number) => {
        const link = linkRefs.current[index];
        const nav = navRef.current;
        if (!link || !nav) return 0;
        return link.offsetLeft + (link.offsetWidth - BAR_WIDTH) / 2;
    }, []);

    const rest = useCallback(
        (index: number) => {
            const bar = barRef.current;
            if (!bar) return;
            cancelAnimationFrame(frame.current);
            pendingStrike = null;
            bar.removeAttribute('style');
            bar.style.transform = `translateX(${xFor(index)}px)`;
            bar.style.opacity = index >= 0 ? '1' : '0';
        },
        [xFor],
    );

    /** The strike: 0.6s, beam out for the first half, collapsing in the second. */
    const strike = useCallback(
        (from: number, to: number) => {
            const bar = barRef.current;
            const nav = navRef.current;
            if (!bar || !nav || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                rest(to);
                return;
            }

            const fromX = xFor(from);
            const toX = xFor(to);
            const spacing = Math.min(Math.abs(toX - fromX), nav.offsetWidth - 60);
            const flip = to < from ? ' rotateY(180deg)' : '';
            const start = performance.now();
            bar.style.opacity = '1';

            const step = (now: number) => {
                const t = now - start;
                const out = clamp(t / (DURATION / 2));
                const back = easeOut(clamp((t - DURATION / 2) / (DURATION / 2)));

                bar.style.transform = `translateX(${fromX + (toX - fromX) * clamp(t / DURATION)}px)${flip}`;
                bar.style.setProperty('--beam-width', `${t < DURATION / 2 ? spacing * out : spacing * (1 - back)}px`);
                bar.style.setProperty('--beam-opacity', String(clamp(t / 100)));
                bar.style.setProperty('--beam-scale-x', String(1 - back));
                bar.style.setProperty('--beam-scale-y', String(1 - 0.75 * back));
                bar.style.setProperty('--beam-strike-x', `${-50 * clamp(t / DURATION)}%`);
                bar.style.setProperty('--beam-mask', `${40 * clamp((t - DURATION / 2) / 500)}%`);

                if (t < DURATION) frame.current = requestAnimationFrame(step);
                else rest(to);
            };
            frame.current = requestAnimationFrame(step);
        },
        [rest, xFor],
    );

    // On arrival: strike from the item clicked on the previous page, or simply rest.
    useLayoutEffect(() => {
        // Cleared when the strike finishes (in rest), not here: effects can run twice
        // (React's development mode does), and the second run must replay the strike.
        const pending = pendingStrike;

        if (pending && pending.from >= 0 && activeIndex >= 0 && pending.from !== activeIndex && Date.now() - pending.at < 4000) {
            strike(pending.from, activeIndex);
        } else {
            rest(activeIndex);
        }

        // Re-align after real size changes only: a ResizeObserver also reports once as
        // soon as it starts watching, which would cut a strike short.
        const nav = navRef.current;
        let width = nav?.offsetWidth;
        const observer =
            nav && typeof ResizeObserver !== 'undefined'
                ? new ResizeObserver(() => {
                      if (nav.offsetWidth === width) return;
                      width = nav.offsetWidth;
                      rest(activeIndex);
                  })
                : null;
        if (nav) observer?.observe(nav);

        return () => {
            observer?.disconnect();
            cancelAnimationFrame(frame.current);
        };
    }, [activeIndex, rest, strike]);

    const onClick = (index: number, event: MouseEvent) => {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0 || index === activeIndex) return;
        pendingStrike = { from: activeIndex, at: Date.now() };
    };

    return (
        <nav
            ref={navRef}
            aria-label={label}
            className={cn(
                'beam-nav relative rounded-full border px-2 backdrop-blur-[15px]',
                'border-white/10 bg-[#1f2b47] text-white dark:border-white/5 dark:bg-[#0b1020]/80',
                className,
            )}
        >
            <ul className="flex items-center gap-1 xl:gap-3">
                {items.map((item, index) => (
                    <li key={item.href}>
                        <Link
                            ref={(el) => {
                                linkRefs.current[index] = el as HTMLAnchorElement | null;
                            }}
                            href={item.href}
                            aria-current={item.active ? 'page' : undefined}
                            onClick={(event) => onClick(index, event)}
                            className={cn(
                                'block rounded-full px-3 py-3 text-sm font-semibold transition-[color,text-shadow] duration-300 xl:px-4',
                                'focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:outline-none',
                                item.active
                                    ? 'text-white'
                                    : 'text-white/60 hover:text-white hover:[text-shadow:0_0_10px_rgb(106_170_255/0.6),0_0_20px_rgb(106_170_255/0.25)]',
                                item.tone === 'sale' && (item.active ? 'text-[#ffb3bc]' : 'text-[#ff9aa5]/80 hover:text-[#ffb3bc]'),
                            )}
                        >
                            {item.label}
                        </Link>
                    </li>
                ))}
            </ul>

            {/* The underline; during a strike it trails the beam and crackle. */}
            <div
                ref={barRef}
                aria-hidden
                // Width as a class (w-7 = BAR_WIDTH): inline styles are cleared after each strike.
                className="beam-active pointer-events-none absolute bottom-1 left-0 h-[3px] w-7 rounded-full bg-white opacity-0"
            >
                <svg viewBox="0 0 116 5" preserveAspectRatio="none">
                    <path d="M0.5 2.5L113 0.534929C114.099 0.515738 115 1.40113 115 2.5C115 3.59887 114.099 4.48426 113 4.46507L0.5 2.5Z" fill={`url(#${gradientId})`} />
                    <defs>
                        <linearGradient id={gradientId} x1="2" y1="2.5" x2="115" y2="2.5" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#6AAAFF" />
                            <stop offset="1" stopColor="white" />
                        </linearGradient>
                    </defs>
                </svg>
                <div className="beam-strike">
                    {[0, 1].map((i) => (
                        <svg key={i} viewBox="0 0 114 12" preserveAspectRatio="none">
                            <g fill="none" stroke="white" strokeWidth="0.75" strokeLinecap="round">
                                <path d={LIGHTNING} />
                            </g>
                        </svg>
                    ))}
                </div>
            </div>
        </nav>
    );
}

export default ModernNavbarGsap;
