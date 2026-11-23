import { InfoPage } from '@/components/shop/info-page';

export default function Contact() {
    return (
        <InfoPage
            title="Contact us"
            description="We’re a small team and we’re happy to help."
            sections={[
                {
                    heading: 'Customer care',
                    body: (
                        <>
                            <p>
                                Email: <a href="mailto:hello@loomandlane.example">hello@loomandlane.example</a>
                            </p>
                            <p>Phone: 020 7946 0321</p>
                            <p>Monday to Friday, 9am–5pm. We aim to reply to every email within one working day.</p>
                        </>
                    ),
                },
                {
                    heading: 'About an order',
                    body: <p>Please include your order reference (it starts with “LL-”) so we can help you faster.</p>,
                },
                {
                    heading: 'Studio',
                    body: (
                        <address className="not-italic">
                            Loom &amp; Lane
                            <br />
                            12 Example Yard
                            <br />
                            London E2 0AA
                        </address>
                    ),
                },
            ]}
            footnote="Sample contact details: replace with your real business information before launch."
        />
    );
}
