import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

export interface ShoppingNavItem {
    label: string;
    href: string;
    /** The page this item leads to is the one being viewed (keeps its underline). */
    active?: boolean;
    /** "sale" draws the label in the sale red. */
    tone?: 'default' | 'sale';
}

export interface ShoppingNavbarUnderlineProps {
    /** Left: the brand. Defaults to a bag-check mark. */
    brand?: ReactNode;
    items?: ShoppingNavItem[];
    /** Right: icon buttons such as search, account and bag. */
    actions?: ReactNode;
    /** Accessible name of the navigation landmark. */
    label?: string;
    /** Classes for the menu list (e.g. "hidden lg:flex" to hide it on phones). */
    menuClassName?: string;
    /** Classes for the actions group. */
    actionsClassName?: string;
    className?: string;
}

/** The bag-check mark from the original design (Bootstrap Icons, MIT). */
export function BagCheckMark({ className }: { className?: string }) {
    return (
        <svg aria-hidden viewBox="0 0 16 16" fill="currentColor" className={cn('size-5', className)}>
            <path
                fillRule="evenodd"
                d="M10.5 3.5a2.5 2.5 0 0 0-5 0V4h5zm1 0V4H15v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4h3.5v-.5a3.5 3.5 0 1 1 7 0m-.646 5.354a.5.5 0 0 0-.708-.708L7.5 10.793 6.354 9.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z"
            />
        </svg>
    );
}

const defaultItems: ShoppingNavItem[] = [
    { label: 'Home', href: '/', active: true },
    { label: 'Women', href: '/shop/women' },
    { label: 'Men', href: '/shop/men' },
    { label: 'Help', href: '/contact' },
];

/**
 * A floating frosted-glass bar: brand on the left, menu links in the middle whose
 * underline draws out from the centre on hover or keyboard focus (and stays under
 * the current page), and icon actions on the right. Light and dark themes;
 * the underline doesn't animate for reduced-motion users.
 */
export function ShoppingNavbarUnderline({
    brand = <BagCheckMark className="size-6" />,
    items = defaultItems,
    actions,
    label = 'Main',
    menuClassName,
    actionsClassName,
    className,
}: ShoppingNavbarUnderlineProps) {
    return (
        <div
            className={cn(
                'rounded-tile mx-auto flex h-16 w-full max-w-7xl items-center gap-4 border pr-2 pl-5 sm:pl-6',
                // Frosted glass, tinted to the theme.
                'border-white/70 bg-white/55 shadow-[0_10px_40px_-12px_rgb(31_43_71/0.28),inset_0_1px_0_rgb(255_255_255/0.8)] backdrop-blur-xl backdrop-saturate-150',
                'dark:border-white/12 dark:bg-white/[0.04] dark:shadow-[0_10px_40px_-12px_rgb(86_120_220/0.3),inset_0_1px_0_rgb(255_255_255/0.08)]',
                className,
            )}
        >
            <div className="flex shrink-0 items-center">{brand}</div>

            <nav aria-label={label} className={cn('flex flex-1 justify-center', menuClassName)}>
                <ul className="flex items-center gap-7 xl:gap-9">
                    {items.map((item) => (
                        <li key={item.href}>
                            <Link
                                href={item.href}
                                aria-current={item.active ? 'page' : undefined}
                                className={cn(
                                    'group relative block py-2 text-[0.9375rem] font-medium transition-colors duration-300 focus-visible:outline-none',
                                    item.active ? 'text-foreground' : 'text-foreground/70 hover:text-foreground focus-visible:text-foreground',
                                    item.tone === 'sale' && 'text-brand hover:text-brand focus-visible:text-brand dark:text-[#ff9aa5]',
                                )}
                            >
                                {item.label}
                                {/* The underline: draws out from the centre. */}
                                <span
                                    aria-hidden
                                    className={cn(
                                        'absolute inset-x-0 bottom-0 h-0.5 origin-center rounded-full bg-current transition-transform duration-[350ms] ease-[cubic-bezier(0.25,1,0.5,1)] motion-reduce:transition-none',
                                        item.active ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100 group-focus-visible:scale-x-100',
                                    )}
                                />
                            </Link>
                        </li>
                    ))}
                </ul>
            </nav>

            {actions && <div className={cn('flex shrink-0 items-center gap-0.5', actionsClassName)}>{actions}</div>}
        </div>
    );
}

export default ShoppingNavbarUnderline;
