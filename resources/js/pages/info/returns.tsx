import { InfoPage } from '@/components/shop/info-page';
import { Link } from '@inertiajs/react';

export default function Returns() {
    return (
        <InfoPage
            title="Returns"
            description="Free returns within 30 days, no questions asked."
            sections={[
                {
                    heading: 'Our returns policy',
                    body: (
                        <p>
                            You can return any unworn item with its original tags within <strong>30 days</strong> of delivery for a full refund.
                        </p>
                    ),
                },
                {
                    heading: 'How to return',
                    body: (
                        <ol className="list-decimal space-y-2 pl-5">
                            <li>Pack the items securely in their original packaging.</li>
                            <li>Include your order reference (it starts with “LL-”).</li>
                            <li>Drop the parcel off using the prepaid label included with your order.</li>
                        </ol>
                    ),
                },
                {
                    heading: 'Refunds',
                    body: <p>Refunds go back to your original payment method within 5 working days of your return reaching us.</p>,
                },
                {
                    heading: 'Exchanges',
                    body: (
                        <p>
                            Need a different size? Return the original item and place a new order, or{' '}
                            <Link href={route('pages.contact')}>contact us</Link> and we’ll help.
                        </p>
                    ),
                },
            ]}
        />
    );
}
