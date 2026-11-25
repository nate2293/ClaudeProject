import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type CSSProperties, type MouseEvent, useCallback, useLayoutEffect, useRef, useState } from 'react';

export interface GlowingNavItem {
    label: string;
    href: string;
    /** The page this item leads to is the one being viewed. */
    active?: boolean;
    /** "sale" draws the label in a red that reads on the dark capsule. */
    tone?: 'default' | 'sale';
}

export interface GlowingNavbarAnimeProps {
    items?: GlowingNavItem[];
    /** Accessible name of the navigation landmark. */
    label?: string;
    className?: string;
}

const defaultItems: GlowingNavItem[] = [
    { label: 'Home', href: '/', active: true },
    { label: 'Shop', href: '/shop' },
    { label: 'About', href: '/about' },
    { label: 'Contact', href: '/contact' },
];

/**
 * A capsule navigation bar with a glowing pill. The pill rests on the current
 * page's item, glides to whichever item is hovered or focused, and returns when
 * the pointer or focus leaves. Items other than the pill's tilt a soft tile
 * towards the cursor. Real links (aria-current marks the current page), full
 * keyboard support, motion off for reduced-motion users, light and dark themes.
 */
export function GlowingNavbarAnime({ items = defaultItems, label = 'Main', className }: GlowingNavbarAnimeProps) {
    const navRef = useRef<HTMLElement>(null);
    const itemRefs = useRef<(HTMLLIElement | null)[]>([]);
    const activeIndex = items.findIndex((item) => item.active);
    const [pill, setPill] = useState<number>(activeIndex);

    // Move the pill (via CSS variables, animated by @property transitions in app.css).
    const place = useCallback((index: number) => {
        const nav = navRef.current;
        const li = itemRefs.current[index];
        if (!nav) return;

        if (index < 0 || !li) {
            nav.style.setProperty('--gn-on', '0');
            return;
        }

        nav.style.setProperty('--gn-x', `${li.offsetLeft}px`);
        nav.style.setProperty('--gn-w', `${li.offsetWidth}px`);
        nav.style.setProperty('--gn-center', `${li.offsetLeft + li.offsetWidth / 2}px`);
        nav.style.setProperty('--gn-on', '1');
    }, []);

    // Rest on the current page; re-measure when the layout changes (fonts, resize).
    useLayoutEffect(() => {
        setPill(activeIndex);
        place(activeIndex);

        const nav = navRef.current;
        if (!nav || typeof ResizeObserver === 'undefined') return;
        const observer = new ResizeObserver(() => place(activeIndex));
        observer.observe(nav);

        return () => observer.disconnect();
    }, [activeIndex, place]);

    const moveTo = (index: number) => {
        setPill(index);
        place(index);
    };

    const tilt = (event: MouseEvent<HTMLAnchorElement>) => {
        const rect = event.currentTarget.getBoundingClientRect();
        const li = event.currentTarget.parentElement;
        li?.style.setProperty('--gn-tilt-y', `${(-(event.clientX - rect.left - rect.width / 2) / 20) * 2}`);
        li?.style.setProperty('--gn-tilt-x', `${(-(event.clientY - rect.top - rect.height / 2) / 20) * 2}`);
    };

    return (
        <nav
            ref={navRef}
            aria-label={label}
            className={cn('glow-nav', className)}
            onMouseLeave={() => moveTo(activeIndex)}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget as Node | null)) moveTo(activeIndex);
            }}
            style={{ '--gn-on': activeIndex >= 0 ? 1 : 0 } as CSSProperties}
        >
            <ul className="flex h-11 items-stretch">
                {items.map((item, index) => (
                    <li
                        key={item.href}
                        ref={(el) => {
                            itemRefs.current[index] = el;
                        }}
                        data-pill={pill === index ? '' : undefined}
                    >
                        <Link
                            href={item.href}
                            aria-current={item.active ? 'page' : undefined}
                            onMouseEnter={() => moveTo(index)}
                            onFocus={() => moveTo(index)}
                            onMouseMove={tilt}
                            className={cn(
                                'relative z-10 flex h-full items-center rounded-full px-4 text-sm font-medium transition-opacity xl:px-5',
                                'focus-visible:ring-2 focus-visible:ring-white/70 focus-visible:outline-none focus-visible:ring-inset',
                                pill === index ? 'opacity-100' : 'opacity-75 hover:opacity-100',
                                item.tone === 'sale' && 'text-[#ff9aa5]',
                            )}
                        >
                            {item.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>
    );
}

export default GlowingNavbarAnime;
