import { Button } from '@/components/ui/button';
import { GlowingNavbarAnime } from '@/components/ui/glowing-navbar-anime';
import { primaryNav } from '@/data/storefront';
import { isCurrentUrl } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';
import { AccountMenu } from './account-menu';
import { Container } from './container';
import { SearchDialog } from './search-dialog';
import { ShopLogo } from './shop-logo';

export function SiteHeader() {
    const { props, url } = usePage<SharedData>();
    const { navigation, cart } = props;
    const links = primaryNav(navigation);

    return (
        <header className="bg-background/85 supports-[backdrop-filter]:bg-background/70 sticky top-0 z-40 backdrop-blur">
            <Container className="flex h-16 items-center gap-4">
                <div className="flex flex-1 items-center gap-2 lg:flex-none">
                    <ShopLogo />
                </div>

                <div className="hidden flex-1 justify-center lg:flex">
                    <GlowingNavbarAnime
                        items={links.map((link) => ({
                            label: link.label,
                            href: link.href,
                            active: isCurrentUrl(link.href, url),
                            tone: link.label === 'Sale' ? 'sale' : 'default',
                        }))}
                    />
                </div>

                {/* Phones and tablets get these in the bottom menu instead (ShopBottomMenu). */}
                <div className="hidden items-center justify-end gap-1 lg:flex">
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
                </div>
            </Container>
        </header>
    );
}
