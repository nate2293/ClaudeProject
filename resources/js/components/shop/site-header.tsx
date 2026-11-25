import { Button } from '@/components/ui/button';
import { BagCheckMark, ShoppingNavbarUnderline } from '@/components/ui/shopping-navbar-underline';
import { primaryNav, storeName } from '@/data/storefront';
import { isCurrentUrl } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';
import { AccountMenu } from './account-menu';
import { SearchDialog } from './search-dialog';

/**
 * The floating glass header: brand, departments and search / account / bag in one
 * bar. Phones and tablets show just the brand here; the bottom menu (ShopBottomMenu)
 * has the rest.
 */
export function SiteHeader() {
    const { props, url } = usePage<SharedData>();
    const { navigation, cart } = props;
    const links = primaryNav(navigation);

    return (
        <header className="sticky top-3 z-40 mt-3 px-3 sm:px-4 lg:top-4 lg:mt-4">
            <ShoppingNavbarUnderline
                brand={
                    <Link href={route('home')} aria-label={`${storeName} home`} className="group flex items-center gap-2.5">
                        <BagCheckMark className="size-[22px] transition-transform duration-300 group-hover:scale-110 motion-reduce:transition-none" />
                        <span className="font-display text-xl">{storeName}</span>
                    </Link>
                }
                items={links.map((link) => ({
                    label: link.label,
                    href: link.href,
                    active: isCurrentUrl(link.href, url),
                    tone: link.label === 'Sale' ? 'sale' : 'default',
                }))}
                menuClassName="hidden lg:flex"
                actionsClassName="hidden lg:flex"
                actions={
                    <>
                        <SearchDialog />
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
                    </>
                }
            />
        </header>
    );
}
