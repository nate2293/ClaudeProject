import { AnimatedNavbar } from '@/components/ui/animated-navbar';
import { Button } from '@/components/ui/button';
import { footerLinks, primaryNav, storeName } from '@/data/storefront';
import { isCurrentUrl } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Search, ShoppingBag } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AccountMenu } from './account-menu';

/** A small underlined search field; Enter searches the shop. */
function HeaderSearch({ initial }: { initial: string }) {
    const [q, setQ] = useState(initial);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (q.trim()) router.get(route('shop.index'), { q: q.trim() });
    };

    return (
        <form role="search" onSubmit={submit} className="mr-2">
            <label htmlFor="header-search" className="sr-only">
                Search the shop
            </label>
            <div className="relative">
                <input
                    id="header-search"
                    type="search"
                    value={q}
                    onChange={(event) => setQ(event.target.value)}
                    placeholder="Search"
                    enterKeyHint="search"
                    className="border-foreground/45 placeholder:text-foreground/80 focus:border-foreground w-40 border-0 border-b bg-transparent py-1.5 pr-7 text-[15px] transition-[border-color,box-shadow] outline-none focus:shadow-[0_1px_0_0_var(--foreground)] xl:w-52 [&::-webkit-search-cancel-button]:hidden"
                />
                <button
                    type="submit"
                    aria-label="Search"
                    className="text-foreground/70 hover:text-foreground focus-visible:ring-ring absolute top-1/2 right-0 -translate-y-1/2 rounded-sm p-0.5 outline-none focus-visible:ring-2"
                >
                    <Search className="size-4" />
                </button>
            </div>
        </form>
    );
}

/**
 * The storefront header: menu on the left, wordmark in the centre, then search,
 * account and bag. Below lg the right-hand side moves to the bottom menu
 * (ShopBottomMenu), so phones show just the menu button and wordmark.
 */
export function SiteHeader() {
    const { props, url } = usePage<SharedData>();
    const { navigation, cart } = props;
    const query = new URLSearchParams(url.split('?')[1] ?? '').get('q') ?? '';

    const items = [{ label: 'Home', href: route('home') }, ...primaryNav(navigation)].map((link) => ({
        ...link,
        active: isCurrentUrl(link.href, url),
    }));

    // Help and About from the footer, so delivery, returns and the legal pages are always one tap away.
    const groups = footerLinks(navigation)
        .filter((group) => group.title !== 'Shop')
        .map((group) => ({ ...group, links: group.links.map((link) => ({ ...link, active: isCurrentUrl(link.href, url) })) }));

    return (
        <AnimatedNavbar
            items={items}
            groups={groups}
            brand={
                <Link
                    href={route('home')}
                    aria-label={`${storeName} home`}
                    className="font-logo focus-visible:ring-ring rounded-md px-1 text-[1.4rem] whitespace-nowrap outline-none focus-visible:ring-2 lg:text-[1.65rem]"
                >
                    {storeName}
                </Link>
            }
            actions={
                <div className="hidden items-center gap-0.5 lg:flex">
                    <HeaderSearch initial={query} />
                    <AccountMenu />
                    <Button variant="ghost" size="icon" asChild className="relative">
                        <Link href={route('cart.index')} aria-label={`Shopping bag, ${cart.count} ${cart.count === 1 ? 'item' : 'items'}`}>
                            <ShoppingBag className="size-5!" />
                            {cart.count > 0 && (
                                <span className="bg-foreground text-background absolute top-1 right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] leading-none font-semibold tabular-nums">
                                    {cart.count > 9 ? '9+' : cart.count}
                                </span>
                            )}
                        </Link>
                    </Button>
                </div>
            }
        />
    );
}
