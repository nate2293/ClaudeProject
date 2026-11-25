import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { House, type LucideIcon, Moon, Search, User } from 'lucide-react';
import { type KeyboardEvent, type ReactNode, useEffect, useId, useLayoutEffect, useRef, useState } from 'react';

export interface BottomMenuItem {
    id: string;
    /** Accessible name, also shown as a tooltip. */
    label: string;
    icon: LucideIcon;
    /** Go straight to a page instead of opening a panel. */
    href?: string;
    /** What opens above the toolbar. Receives `close` for links and buttons inside. */
    panel?: ReactNode | ((close: () => void) => ReactNode);
    /** A small count on the icon, e.g. items in the bag. */
    badge?: number;
    /** Highlight, e.g. the page this item leads to is open. */
    active?: boolean;
}

export interface BottomMenuProps {
    items?: BottomMenuItem[];
    /** Accessible name of the navigation landmark. */
    label?: string;
    className?: string;
}

const defaultItems: BottomMenuItem[] = [
    { id: 'home', label: 'Home', icon: House, href: '/' },
    { id: 'search', label: 'Search', icon: Search, panel: <p className="w-56 p-3 text-sm">Search goes here.</p> },
    { id: 'account', label: 'Account', icon: User, panel: <p className="w-56 p-3 text-sm">Account links go here.</p> },
    { id: 'theme', label: 'Theme', icon: Moon, panel: <p className="w-56 p-3 text-sm">Theme options go here.</p> },
];

const EXIT_MS = 220;

/**
 * A floating toolbar fixed to the bottom of the screen. Buttons either go to a
 * page or open a panel above the toolbar; the panel resizes smoothly to fit each
 * section and its content swaps with a soft blur.
 *
 * Keyboard: Tab or ←/→ between buttons, Enter/Space to open, Escape to close
 * (focus returns to the button). Tapping outside or navigating also closes it.
 * Sits above the iPhone home indicator; motion is off for reduced-motion users.
 */
export function BottomMenu({ items = defaultItems, label = 'Menu', className }: BottomMenuProps) {
    const [open, setOpen] = useState<string | null>(null);
    const [shown, setShown] = useState<string | null>(null); // Lags behind `open` while the panel closes.
    const [size, setSize] = useState<{ width: number; height: number }>();
    const container = useRef<HTMLDivElement>(null);
    const content = useRef<HTMLDivElement>(null);
    const buttons = useRef<(HTMLElement | null)[]>([]);
    const panelId = useId();

    const close = (returnFocus = false) => {
        const index = items.findIndex((item) => item.id === open);
        setOpen(null);
        if (returnFocus && index >= 0) buttons.current[index]?.focus();
    };

    // Keep the panel mounted until its closing animation has played.
    useEffect(() => {
        if (open) {
            setShown(open);
            return;
        }
        const timer = setTimeout(() => setShown(null), EXIT_MS);
        return () => clearTimeout(timer);
    }, [open]);

    // The panel's frame follows its content's size, so switching sections animates smoothly.
    useLayoutEffect(() => {
        const el = content.current;
        if (!el || !shown) return;
        const measure = () => setSize({ width: el.offsetWidth, height: el.offsetHeight });
        measure();
        const observer = new ResizeObserver(measure);
        observer.observe(el);
        return () => observer.disconnect();
    }, [shown]);

    // Close on tap outside and on navigation.
    useEffect(() => {
        const onPointerDown = (event: PointerEvent) => {
            if (container.current && !container.current.contains(event.target as Node)) setOpen(null);
        };
        document.addEventListener('pointerdown', onPointerDown);
        const stopListening = router.on('navigate', () => setOpen(null));

        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            stopListening();
        };
    }, []);

    // Move focus into a newly opened panel (e.g. the search box).
    useEffect(() => {
        if (!open) return;
        const frame = requestAnimationFrame(() => {
            content.current?.querySelector<HTMLElement>('[autofocus], input, button, a[href]')?.focus();
        });
        return () => cancelAnimationFrame(frame);
    }, [open]);

    const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        if (event.key === 'Escape' && open) {
            event.stopPropagation();
            close(true);
            return;
        }

        const step = { ArrowRight: 1, ArrowLeft: -1 }[event.key];
        const index = buttons.current.findIndex((button) => button === document.activeElement);
        if (step && index >= 0) {
            event.preventDefault();
            buttons.current[(index + step + items.length) % items.length]?.focus();
        }
    };

    const panelItem = items.find((item) => item.id === shown);
    const panel = panelItem && (typeof panelItem.panel === 'function' ? panelItem.panel(() => close()) : panelItem.panel);

    return (
        <div
            ref={container}
            onKeyDown={onKeyDown}
            className={cn('fixed inset-x-0 bottom-[calc(env(safe-area-inset-bottom)+0.75rem)] z-40 flex justify-center px-3', className)}
        >
            <div className="relative">
                {/* Panel */}
                <div
                    id={panelId}
                    role="region"
                    aria-label={panelItem?.label}
                    hidden={!shown}
                    style={size ? { width: size.width, height: size.height } : undefined}
                    className={cn(
                        'border-border bg-background/95 rounded-tile absolute bottom-[calc(100%+0.625rem)] left-1/2 max-w-[calc(100vw-1.5rem)] -translate-x-1/2 overflow-hidden border shadow-[0_18px_40px_-12px_rgb(0_0_0/0.35)] backdrop-blur-xl',
                        'origin-bottom transition-[width,height,opacity,scale] duration-300 ease-[cubic-bezier(0.45,0,0.25,1)] motion-reduce:transition-none',
                        open ? 'scale-100 opacity-100' : 'pointer-events-none scale-95 opacity-0',
                    )}
                >
                    <div ref={content} className="w-max">
                        <div key={shown ?? 'none'} className="motion-safe:animate-[bm-swap_250ms_ease_both]">
                            {panel}
                        </div>
                    </div>
                </div>

                {/* Toolbar */}
                <nav aria-label={label} className="border-border bg-background/95 rounded-tile flex items-center gap-1 border p-1 shadow-[0_10px_30px_-10px_rgb(0_0_0/0.3)] backdrop-blur-xl">
                    {items.map((item, index) => {
                        const expanded = open === item.id;
                        const iconClass = cn('size-5 transition-colors', expanded || item.active ? 'text-foreground' : 'text-muted-foreground');
                        const buttonClass = cn(
                            'focus-visible:ring-ring relative grid size-12 place-items-center rounded-[12px] transition-colors focus-visible:ring-2 focus-visible:outline-none',
                            expanded ? 'bg-secondary' : item.active ? 'bg-secondary/60' : 'hover:bg-secondary/50',
                        );
                        const badge = item.badge ? (
                            <span className="bg-foreground text-background absolute top-1.5 right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] leading-none font-semibold tabular-nums">
                                {item.badge > 9 ? '9+' : item.badge}
                            </span>
                        ) : null;
                        const ref = (el: HTMLElement | null) => {
                            buttons.current[index] = el;
                        };

                        return item.href ? (
                            <Link
                                key={item.id}
                                ref={ref}
                                href={item.href}
                                aria-label={item.badge ? `${item.label}, ${item.badge} ${item.badge === 1 ? 'item' : 'items'}` : item.label}
                                aria-current={item.active ? 'page' : undefined}
                                title={item.label}
                                className={buttonClass}
                            >
                                <item.icon className={iconClass} strokeWidth={1.75} aria-hidden />
                                {badge}
                            </Link>
                        ) : (
                            <button
                                key={item.id}
                                ref={ref}
                                type="button"
                                aria-label={item.label}
                                aria-expanded={expanded}
                                aria-controls={panelId}
                                title={item.label}
                                onClick={() => setOpen(expanded ? null : item.id)}
                                className={buttonClass}
                            >
                                <item.icon className={iconClass} strokeWidth={1.75} aria-hidden />
                                {badge}
                            </button>
                        );
                    })}
                </nav>
            </div>
        </div>
    );
}

/** Row styling for links and buttons inside a panel. */
export const bottomMenuRow =
    'text-muted-foreground hover:bg-secondary/60 hover:text-foreground focus-visible:ring-ring flex w-full items-center gap-3 rounded-[12px] px-3 py-2.5 text-left text-[15px] transition-colors focus-visible:ring-2 focus-visible:outline-none';

export default BottomMenu;
