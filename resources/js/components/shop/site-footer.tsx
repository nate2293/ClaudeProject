import { AnimatedLinksFooter } from '@/components/ui/animated-links-footer';
import { footerLinks, storeName } from '@/data/storefront';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ShopLogo } from './shop-logo';

/** The storefront footer: brand, Shop / Help / About links, and the legal line. */
export function SiteFooter() {
    const { navigation } = usePage<SharedData>().props;

    return (
        <AnimatedLinksFooter
            brand={<ShopLogo className="text-xl" />}
            description="Considered clothing in natural fibres, designed in London and made to be worn for years."
            columns={footerLinks(navigation)}
            legal={`© ${new Date().getFullYear()} ${storeName}. All rights reserved.`}
            note="Payments securely processed by Stripe."
        />
    );
}
