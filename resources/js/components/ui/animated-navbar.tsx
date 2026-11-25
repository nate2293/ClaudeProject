import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { X } from 'lucide-react';
import { type CSSProperties, type ReactNode, useCallback, useEffect, useId, useRef, useState } from 'react';

export interface AnimatedNavItem {
    label: string;
    href: string;
    /** The page this item leads to is the one being viewed. */
    active?: boolean;
}

export interface AnimatedNavGroup {
    title: string;
    links: AnimatedNavItem[];
}

export interface AnimatedNavbarProps {
    /** Centre: the wordmark. */
    brand?: ReactNode;
    /** Large links in the full-screen menu. */
    items?: AnimatedNavItem[];
    /** Smaller grouped links beside them (help, legal and so on). */
    groups?: AnimatedNavGroup[];
    /** Right: search, account, bag. */
    actions?: ReactNode;
    /** Accessible name of the menu's navigation landmark. */
    label?: string;
    className?: string;
}

const defaultItems: AnimatedNavItem[] = [
    { label: 'Home', href: '/', active: true },
    { label: 'Discover', href: '/shop' },
    { label: 'About', href: '/about' },
];

// Close to GSAP's power4.out, and a springy overshoot for the links.
const EASE_OUT = 'cubic-bezier(0.23, 1, 0.32, 1)';
const EASE_SPRING = 'cubic-bezier(0.34, 1.45, 0.64, 1)';

/**
 * A three-part header: a menu button on the left, the brand in the centre and
 * actions on the right. The button opens a full-screen menu that grows as a
 * circle out of the button, with its links springing in one after another;
 * closing reverses it. While open, the rest of the page is inert, focus stays
 * in the menu and Escape closes it. Reduced-motion users get it without the
 * animation.
 */
export function AnimatedNavbar({ brand, items = defaultItems, groups = [], actions, label = 'Main', className }: AnimatedNavbarProps) {
    const [open, setOpen] = useState(false);
    const [origin, setOrigin] = useState({ x: 0, y: 0, r: 0 });
    const headerRef = useRef<HTMLElement>(null);
    const toggleRef = useRef<HTMLButtonElement>(null);
    const panelRef = useRef<HTMLDivElement>(null);
    const panelId = useId();

    const toggle = () => {
        if (!open && toggleRef.current) {
            // Grow the circle from the button's centre to the farthest corner.
            const rect = toggleRef.current.getBoundingClientRect();
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;
            const r = Math.hypot(Math.max(x, window.innerWidth - x), Math.max(y, window.innerHeight - y));
            setOrigin({ x, y, r });
        }
        setOpen((value) => !value);
    };

    const close = useCallback((returnFocus = true) => {
        setOpen(false);
        if (returnFocus) toggleRef.current?.focus();
    }, []);

    useEffect(() => {
        if (!open) return;

        const header = headerRef.current;
        const root = document.documentElement;

        // Make everything outside the header inert, as a modal would.
        const inerted: Element[] = [];
        for (let el: Element | null = header; el && el !== document.body; el = el.parentElement) {
            for (const sibling of Array.from(el.parentElement?.children ?? [])) {
                if (sibling !== el && !sibling.hasAttribute('inert') && sibling.tagName !== 'SCRIPT') {
                    sibling.setAttribute('inert', '');
                    inerted.push(sibling);
                }
            }
        }

        const overflow = root.style.overflow;
        root.style.overflow = 'hidden';

        panelRef.current?.querySelector<HTMLElement>('a[href]')?.focus({ preventScroll: true });

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
                return;
            }

            if (event.key !== 'Tab') return;

            // Keep focus cycling through the button and the menu's links.
            const focusable = [toggleRef.current, ...Array.from(panelRef.current?.querySelectorAll<HTMLElement>('a[href]') ?? [])].filter(
                (el): el is HTMLElement => !!el,
            );
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            inerted.forEach((el) => el.removeAttribute('inert'));
            root.style.overflow = overflow;
        };
    }, [open, close]);

    const circle = (radius: number) => `circle(${radius}px at ${origin.x}px ${origin.y}px)`;
    const linkCount = items.length;

    return (
        <header ref={headerRef} className={cn('sticky top-0', open ? 'z-[60]' : 'z-40', className)}>
            {/* The bar's background. Kept off the header so the menu's fixed position stays relative to the viewport. */}
            <div aria-hidden className="bg-background/85 absolute inset-0 backdrop-blur-md backdrop-saturate-150" />

            <div className="relative mx-auto grid h-16 max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-4 px-3 sm:px-5 lg:h-20 lg:px-8">
                <div className="flex items-center">
                    <button
                        ref={toggleRef}
                        type="button"
                        onClick={toggle}
                        aria-expanded={open}
                        aria-controls={panelId}
                        aria-label="Menu"
                        className={cn(
                            'focus-visible:ring-ring relative z-20 grid size-11 place-items-center rounded-full transition-[color,scale] duration-500 outline-none focus-visible:ring-2 active:scale-90',
                            open ? 'text-primary-foreground focus-visible:ring-primary-foreground' : 'text-foreground',
                        )}
                    >
                        <span className="relative block h-6 w-8 overflow-hidden">
                            {/* Two thin lines that lift out as the cross lifts in. */}
                            <span
                                aria-hidden
                                className={cn(
                                    'absolute inset-0 flex flex-col justify-center gap-[7px] transition-[translate,opacity] duration-700 motion-reduce:transition-none',
                                    open ? '-translate-y-[125%] opacity-0' : 'translate-y-0 opacity-100',
                                )}
                                style={{ transitionTimingFunction: EASE_OUT }}
                            >
                                <span className="h-[1.5px] w-full rounded-full bg-current" />
                                <span className="h-[1.5px] w-full rounded-full bg-current" />
                            </span>
                            <span
                                aria-hidden
                                className={cn(
                                    'absolute inset-0 grid place-items-center transition-[translate,opacity] duration-700 motion-reduce:transition-none',
                                    open ? 'translate-y-0 opacity-100' : 'translate-y-[125%] opacity-0',
                                )}
                                style={{ transitionTimingFunction: EASE_OUT }}
                            >
                                <X className="size-7" strokeWidth={1.5} />
                            </span>
                        </span>
                    </button>
                </div>

                <div inert={open} className="flex justify-center">
                    {brand}
                </div>

                <div inert={open} className="flex items-center justify-end gap-1">
                    {actions}
                </div>
            </div>

            {/* The full-screen menu, revealed by a growing circle. */}
            <div
                ref={panelRef}
                id={panelId}
                inert={!open}
                className={cn(
                    'bg-primary text-primary-foreground fixed inset-0 z-10 overflow-y-auto motion-reduce:transition-none',
                    // Visible straight away on open (so focus can move in); hidden only once the circle has closed.
                    open ? 'visible transition-[clip-path] duration-[900ms]' : 'invisible transition-[clip-path,visibility] delay-150 duration-700',
                )}
                style={{ clipPath: circle(open ? origin.r : 0), transitionTimingFunction: EASE_OUT }}
            >
                <nav aria-label={label} className="mx-auto flex min-h-full max-w-7xl flex-col justify-center gap-14 px-6 pt-28 pb-16 sm:px-12 lg:flex-row lg:items-center lg:justify-between lg:px-20">
                    <ul className="space-y-1 pl-10 sm:pl-14">
                        {items.map((item, index) => (
                            <li
                                key={item.href}
                                className={cn(
                                    'transition-[translate,opacity] motion-reduce:transition-none',
                                    open ? 'translate-x-0 opacity-100 duration-[1100ms]' : '-translate-x-48 opacity-0 duration-500',
                                )}
                                style={
                                    {
                                        transitionTimingFunction: open ? EASE_SPRING : EASE_OUT,
                                        transitionDelay: `${(open ? index : linkCount - 1 - index) * (open ? 70 : 35)}ms`,
                                    } as CSSProperties
                                }
                            >
                                <Link
                                    href={item.href}
                                    onClick={() => close(false)}
                                    aria-current={item.active ? 'page' : undefined}
                                    className={cn(
                                        'group font-display relative inline-block py-1 text-[clamp(2.25rem,6vw,4.75rem)] leading-[1.1] transition-opacity duration-300 outline-none',
                                        item.active ? 'opacity-100' : 'opacity-70 hover:opacity-100 focus-visible:opacity-100',
                                    )}
                                >
                                    {/* The marker that slides in beside the hovered, focused or current link. */}
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'absolute top-1/2 -left-10 h-2 w-6 -translate-y-1/2 rounded-full bg-current transition-[translate,opacity] duration-300 motion-reduce:transition-none sm:-left-14 sm:w-8',
                                            item.active
                                                ? 'translate-x-0 opacity-100'
                                                : '-translate-x-3 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100',
                                        )}
                                    />
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>

                    {groups.length > 0 && (
                        <div
                            className={cn(
                                'grid grid-cols-2 gap-x-12 gap-y-8 pl-10 transition-[translate,opacity] motion-reduce:transition-none sm:pl-14 lg:pl-0',
                                open ? 'translate-y-0 opacity-100 delay-300 duration-700' : 'translate-y-4 opacity-0 duration-300',
                            )}
                            style={{ transitionTimingFunction: EASE_OUT }}
                        >
                            {groups.map((group) => (
                                <div key={group.title}>
                                    <h2 className="text-primary-foreground/75 mb-3 text-sm">{group.title}</h2>
                                    <ul className="space-y-2">
                                        {group.links.map((link) => (
                                            <li key={link.href}>
                                                <Link
                                                    href={link.href}
                                                    onClick={() => close(false)}
                                                    aria-current={link.active ? 'page' : undefined}
                                                    className="focus-visible:ring-primary-foreground rounded-sm text-base underline-offset-4 outline-none hover:underline focus-visible:ring-2 aria-[current=page]:underline"
                                                >
                                                    {link.label}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    )}
                </nav>
            </div>
        </header>
    );
}

export default AnimatedNavbar;
