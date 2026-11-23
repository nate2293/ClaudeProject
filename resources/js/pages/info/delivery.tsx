import { InfoPage } from '@/components/shop/info-page';
import { formatPrice } from '@/lib/format';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function Delivery() {
    const { freeShippingThreshold, standardShippingRate } = usePage<SharedData>().props.shop;

    return (
        <InfoPage
            title="Delivery"
            description="Everything you need to know about getting your order."
            sections={[
                {
                    heading: 'UK delivery',
                    body: (
                        <>
                            <p>
                                <strong>Standard delivery (2–4 working days):</strong> {formatPrice(standardShippingRate)}, or free on orders over{' '}
                                {formatPrice(freeShippingThreshold)}.
                            </p>
                            <p>Orders placed before 1pm on a working day are dispatched the same day.</p>
                        </>
                    ),
                },
                {
                    heading: 'Tracking your order',
                    body: <p>As soon as your order leaves our studio you’ll receive an email with a tracking link.</p>,
                },
                {
                    heading: 'International delivery',
                    body: <p>We currently deliver to UK addresses only. We’re working on shipping further afield soon.</p>,
                },
                {
                    heading: 'Need help?',
                    body: (
                        <p>
                            If your order hasn’t arrived within the expected time, please <Link href={route('pages.contact')}>get in touch</Link>.
                        </p>
                    ),
                },
            ]}
        />
    );
}
