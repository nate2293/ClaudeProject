import { BottomMenu, type BottomMenuItem, bottomMenuRow } from '@/components/ui/bottom-menu';
import { primaryNav } from '@/data/storefront';
import { type Appearance, useAppearance } from '@/hooks/use-appearance';
import { cn, isCurrentUrl } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { LayoutGrid, LogOut, Monitor, Moon, Package, Search, ShoppingBag, Sun, User, UserRound } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { SignInOptions } from './account-menu';

const themes: { key: Appearance; label: string; icon: typeof Sun }[] = [
    { key: 'light', label: 'Light', icon: Sun },
    { key: 'dark', label: 'Dark', icon: Moon },
    { key: 'system', label: 'System', icon: Monitor },
];

function SearchPanel({ close }: { close: () => void }) {
    const [q, setQ] = useState('');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!q.trim()) return;
        router.get(route('shop.index'), { q: q.trim() });
        close();
    };

    return (
        <form role="search" onSubmit={submit} className="w-[min(18rem,calc(100vw-3rem))] p-2">
            <label htmlFor="bottom-search" className="sr-only">
                Search the shop
            </label>
            <div className="relative">
                <Search className="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" aria-hidden />
                <input
                    id="bottom-search"
                    type="search"
                    value={q}
                    onChange={(event) => setQ(event.target.value)}
                    placeholder="Jumpers, linen, navy…"
                    enterKeyHint="search"
                    className="bg-secondary/60 border-border focus:ring-ring w-full rounded-[12px] border py-2.5 pr-3 pl-9 text-[15px] outline-none focus:ring-2"
                />
            </div>
        </form>
    );
}

/**
 * The shop's navigation on phones and tablets (where the header capsule doesn't
 * fit): departments, search, bag, account and theme in a floating bottom toolbar.
 */
export function ShopBottomMenu() {
    const { props, url } = usePage<SharedData>();
    const { auth, cart, navigation } = props;
    const { appearance, updateAppearance } = useAppearance();
    const links = primaryNav(navigation);

    const items: BottomMenuItem[] = [
        {
            id: 'shop',
            label: 'Shop',
            icon: LayoutGrid,
            active: links.some((link) => isCurrentUrl(link.href, url)),
            panel: (
                <ul className="w-56 space-y-0.5 p-1.5">
                    {links.map((link) => {
                        const current = isCurrentUrl(link.href, url);
                        return (
                            <li key={link.label}>
                                <Link
                                    href={link.href}
                                    aria-current={current ? 'page' : undefined}
                                    className={cn(
                                        bottomMenuRow,
                                        current && 'text-foreground bg-secondary/60 font-medium',
                                        link.label === 'Sale' && 'text-brand',
                                    )}
                                >
                                    {link.label}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            ),
        },
        { id: 'search', label: 'Search', icon: Search, panel: (close) => <SearchPanel close={close} /> },
        { id: 'bag', label: 'Bag', icon: ShoppingBag, href: route('cart.index'), badge: cart.count, active: url.startsWith('/cart') },
        {
            id: 'account',
            label: auth.user ? 'Account' : 'Sign in',
            icon: User,
            active: url.startsWith('/account'),
            panel: auth.user ? (
                <div className="w-60 p-1.5">
                    <p className="text-muted-foreground truncate px-3 pt-1.5 pb-2 text-xs">Signed in as {auth.user.name}</p>
                    <Link href={route('account.orders')} className={bottomMenuRow}>
                        <Package className="size-4" aria-hidden />
                        My orders
                    </Link>
                    <Link href={route('profile.edit')} className={bottomMenuRow}>
                        <UserRound className="size-4" aria-hidden />
                        My account
                    </Link>
                    <div className="border-border my-1 border-t" />
                    <Link href={route('logout')} method="post" as="button" className={cn(bottomMenuRow, 'text-brand hover:text-brand')}>
                        <LogOut className="size-4" aria-hidden />
                        Sign out
                    </Link>
                </div>
            ) : (
                <div className="w-[min(18rem,calc(100vw-3rem))] p-4">
                    <SignInOptions />
                </div>
            ),
        },
        {
            id: 'theme',
            label: 'Theme',
            icon: appearance === 'dark' ? Moon : appearance === 'light' ? Sun : Monitor,
            panel: (
                <div role="radiogroup" aria-label="Theme" className="flex gap-1 p-1.5">
                    {themes.map(({ key, label, icon: Icon }) => (
                        <button
                            key={key}
                            type="button"
                            role="radio"
                            aria-checked={appearance === key}
                            onClick={() => updateAppearance(key)}
                            className={cn(
                                'focus-visible:ring-ring flex items-center gap-2 rounded-[12px] px-3 py-2.5 text-sm transition-colors focus-visible:ring-2 focus-visible:outline-none',
                                appearance === key ? 'bg-secondary text-foreground font-medium' : 'text-muted-foreground hover:bg-secondary/50',
                            )}
                        >
                            <Icon className="size-4" aria-hidden />
                            {label}
                        </button>
                    ))}
                </div>
            ),
        },
    ];

    return <BottomMenu items={items} label="Shop menu" className="lg:hidden" />;
}
