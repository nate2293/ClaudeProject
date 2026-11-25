import { FlashMessage } from '@/components/shop/flash-message';
import { ShopBottomMenu } from '@/components/shop/shop-bottom-menu';
import { SiteFooter } from '@/components/shop/site-footer';
import { SiteHeader } from '@/components/shop/site-header';
import { Head } from '@inertiajs/react';
import { type ReactNode } from 'react';

interface ShopLayoutProps {
    title?: string;
    children: ReactNode;
    /** Show the site footer (default). The landing page leaves it out. */
    footer?: boolean;
}

/**
 * Shared shell for every public storefront page: sticky header, main content area and footer.
 */
export default function ShopLayout({ title, children, footer = true }: ShopLayoutProps) {
    return (
        <>
            {title && <Head title={title} />}

            <div className="flex min-h-screen flex-col">
                <a
                    href="#main"
                    className="bg-foreground text-background sr-only z-50 rounded-md px-4 py-2 focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
                >
                    Skip to content
                </a>

                <SiteHeader />
                <FlashMessage />

                <main id="main" className="flex-1">
                    {children}
                </main>

                {footer && <SiteFooter />}
                {/* Room for the floating bottom menu, so it never covers the footer. */}
                <div aria-hidden className="h-[calc(env(safe-area-inset-bottom)+5.5rem)] lg:hidden" />
            </div>

            <ShopBottomMenu />
        </>
    );
}
