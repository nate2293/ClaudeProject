import { CtaFooter } from '@/components/ui/cta-footer';
import { footerLinks, storeName } from '@/data/storefront';
import { formatPrice } from '@/lib/format';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/** The storefront footer: a shop call to action, Shop / Help / About links, and the legal lines. */
export function SiteFooter() {
    const { navigation, shop } = usePage<SharedData>().props;
    const { legalName, companyNumber, vatNumber } = shop.business;

    // Registered company details, as UK company websites must show them, once configured.
    const registration = [companyNumber && `Company no. ${companyNumber}`, vatNumber && `VAT no. ${vatNumber}`].filter(Boolean).join(', ');

    return (
        <CtaFooter
            title="Clothes for years, not seasons"
            description={`Free UK delivery over ${formatPrice(shop.freeShippingThreshold)} and free returns within ${shop.returnDays} days.`}
            action={{ label: 'Shop the collection', href: route('shop.index') }}
            columns={footerLinks(navigation)}
            legal={
                <>
                    <p>
                        © {new Date().getFullYear()} {legalName || storeName}. All rights reserved.
                        {registration && ` ${registration}.`}
                    </p>
                    <p>Payments securely processed by Stripe.</p>
                </>
            }
        />
    );
}
