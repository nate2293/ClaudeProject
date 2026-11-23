import { BusinessDetail } from '@/components/shop/business-detail';
import { InfoPage } from '@/components/shop/info-page';
import { formatPrice } from '@/lib/format';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function Terms() {
    const { business, freeShippingThreshold, standardShippingRate } = usePage<SharedData>().props.shop;
    const email = business.email ? <a href={`mailto:${business.email}`}>{business.email}</a> : <BusinessDetail value={null} label="contact email" />;

    return (
        <InfoPage
            title="Terms of sale"
            description="The terms that apply when you buy from us. They don't affect your legal rights as a consumer."
            sections={[
                {
                    heading: 'About us',
                    body: (
                        <p>
                            This shop is run by {business.legalName}, <BusinessDetail value={business.address} label="business address" />.
                            {business.companyNumber && <> Registered in England and Wales, company number {business.companyNumber}.</>}
                            {business.vatNumber && <> VAT number {business.vatNumber}.</>} Contact us at {email}.
                        </p>
                    ),
                },
                {
                    heading: 'Your order',
                    body: (
                        <p>
                            When you pay, you make an offer to buy. A contract is formed when we email you to confirm your order. If we can’t accept
                            it, for example because an item has sold out, we’ll tell you and you won’t be charged (or will be refunded in full).
                        </p>
                    ),
                },
                {
                    heading: 'Prices and payment',
                    body: (
                        <p>
                            Prices are in pounds sterling and include VAT. Delivery is {formatPrice(standardShippingRate)}, or free on orders over{' '}
                            {formatPrice(freeShippingThreshold)}; the delivery charge is shown before you pay. Payments are processed securely by
                            Stripe.
                        </p>
                    ),
                },
                {
                    heading: 'Delivery',
                    body: (
                        <p>
                            We deliver to UK addresses, usually within 2–4 working days of your order, and we’ll email you tracking details when it
                            ships. The goods are your responsibility once delivered. See{' '}
                            <Link href={route('pages.delivery')}>delivery information</Link>.
                        </p>
                    ),
                },
                {
                    heading: 'Changing your mind',
                    body: (
                        <p>
                            By law you can cancel within 14 days of receiving your order, without giving a reason, and you then have a further 14 days
                            to send the items back. Our <Link href={route('pages.returns')}>returns policy</Link> goes further and gives you 30 days.
                            We refund the price of returned items, and the original standard delivery charge if you return the whole order, within 14
                            days of receiving them back.
                        </p>
                    ),
                },
                {
                    heading: 'Faulty items',
                    body: (
                        <p>
                            Under the Consumer Rights Act 2015, goods must be as described, fit for purpose and of satisfactory quality. If something
                            is faulty, tell us within 30 days for a full refund; after that you’re entitled to a repair or replacement, or in some
                            cases a refund. We cover the cost of returning faulty items.
                        </p>
                    ),
                },
                {
                    heading: 'Our responsibility to you',
                    body: (
                        <p>
                            We’re responsible for loss or damage you suffer that is a foreseeable result of us breaking these terms or failing to use
                            reasonable care. We don’t exclude liability where it would be unlawful to do so, such as for death or personal injury
                            caused by our negligence, or for fraud.
                        </p>
                    ),
                },
                {
                    heading: 'Law and disputes',
                    body: (
                        <p>
                            These terms are governed by the law of England and Wales. If you live in Scotland or Northern Ireland you can also bring
                            proceedings there. If you have a complaint, please contact us first at {email} and we’ll try to put it right.
                        </p>
                    ),
                },
            ]}
            footnote="Last updated November 2026. Draft prepared for this shop's setup: have it reviewed by a legal professional before launch."
        />
    );
}
