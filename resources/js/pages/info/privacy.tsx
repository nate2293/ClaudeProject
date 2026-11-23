import { InfoPage } from '@/components/shop/info-page';

export default function Privacy() {
    return (
        <InfoPage
            title="Privacy policy"
            description="How we collect, use and protect your personal information."
            sections={[
                {
                    heading: 'What we collect',
                    body: <p>When you place an order we collect your name, email address and delivery address so we can fulfil it.</p>,
                },
                {
                    heading: 'Payments',
                    body: (
                        <p>
                            Payments are processed by Stripe. Your card details are entered on Stripe’s secure checkout and are never stored on our
                            servers.
                        </p>
                    ),
                },
                {
                    heading: 'Marketing',
                    body: <p>We only send marketing emails if you sign up to our newsletter, and you can unsubscribe at any time.</p>,
                },
                {
                    heading: 'Your rights',
                    body: <p>You can ask us for a copy of the data we hold about you, or ask us to delete it, by contacting customer care.</p>,
                },
            ]}
            footnote="Sample policy text: have this reviewed by a legal professional before launch."
        />
    );
}
