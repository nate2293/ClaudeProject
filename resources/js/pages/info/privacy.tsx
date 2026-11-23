import { BusinessDetail } from '@/components/shop/business-detail';
import { InfoPage } from '@/components/shop/info-page';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function Privacy() {
    const { business } = usePage<SharedData>().props.shop;
    const email = business.email ? <a href={`mailto:${business.email}`}>{business.email}</a> : <BusinessDetail value={null} label="contact email" />;

    return (
        <InfoPage
            title="Privacy policy"
            description="What personal information we collect, why, who we share it with and your rights."
            sections={[
                {
                    heading: 'Who we are',
                    body: (
                        <>
                            <p>
                                {business.legalName} (“we”) runs this shop and is the controller of your personal data. Our address is{' '}
                                <BusinessDetail value={business.address} label="business address" />. You can contact us about privacy at {email}.
                            </p>
                            <p>
                                We are registered with the UK Information Commissioner’s Office:{' '}
                                <BusinessDetail value={business.icoNumber} label="ICO registration number" />.
                            </p>
                        </>
                    ),
                },
                {
                    heading: 'What we collect and why',
                    body: (
                        <ul className="list-disc space-y-2 pl-5">
                            <li>
                                <strong>When you order:</strong> your name, email, delivery address and what you bought. We need this to take payment,
                                deliver your order and handle returns (performing our contract with you).
                            </li>
                            <li>
                                <strong>Payment:</strong> you enter your card details on Stripe’s secure page. We never see or store your full card
                                number; we receive a payment reference and the outcome.
                            </li>
                            <li>
                                <strong>If you create an account:</strong> your name, email and a securely hashed password, so you can sign in.
                            </li>
                            <li>
                                <strong>Newsletter:</strong> your email, only after you confirm the sign-up link we send you (your consent). Every
                                email has a one-click unsubscribe link.
                            </li>
                            <li>
                                <strong>Keeping the shop secure and working:</strong> technical details such as your IP address in server logs and
                                error reports, used to prevent fraud and fix faults (our legitimate interests).
                            </li>
                        </ul>
                    ),
                },
                {
                    heading: 'Who we share it with',
                    body: (
                        <>
                            <p>We only share what each provider needs to do its job for us:</p>
                            <ul className="list-disc space-y-2 pl-5">
                                <li>
                                    <strong>Stripe</strong> processes payments. Stripe also uses payment data to prevent fraud as an independent
                                    controller; see <a href="https://stripe.com/gb/privacy">Stripe’s privacy policy</a>.
                                </li>
                                <li>
                                    <strong>Our hosting provider</strong> (<BusinessDetail value={business.host} label="hosting provider" />) stores
                                    the shop’s data.
                                </li>
                                <li>
                                    <strong>Our email provider</strong> (<BusinessDetail value={business.emailProvider} label="email provider" />)
                                    sends order and newsletter emails.
                                </li>
                                <li>
                                    <strong>Sentry</strong> receives error reports so we can fix faults. These are set up not to include your name,
                                    email or address.
                                </li>
                                <li>
                                    <strong>Delivery companies</strong> receive your name and delivery address to deliver your parcel.
                                </li>
                            </ul>
                            <p>
                                Some providers process data outside the UK, for example in the United States. Where they do, the transfer is covered
                                by UK adequacy regulations or the UK International Data Transfer Agreement. We never sell your data.
                            </p>
                        </>
                    ),
                },
                {
                    heading: 'How long we keep it',
                    body: (
                        <ul className="list-disc space-y-2 pl-5">
                            <li>Orders: 6 years after purchase, as UK tax law requires.</li>
                            <li>Checkouts you didn’t complete: deleted 30 days after the payment page expired.</li>
                            <li>Newsletter: until you unsubscribe. Sign-ups not confirmed within 7 days are deleted.</li>
                            <li>Accounts: until you delete your account (you can do this in your account settings).</li>
                        </ul>
                    ),
                },
                {
                    heading: 'Cookies',
                    body: (
                        <p>
                            We only use cookies the shop needs to work: one keeps your bag and sign-in (session), one protects forms against forgery
                            (XSRF-TOKEN), and your account area remembers whether its sidebar is open. We don’t use advertising or analytics cookies,
                            so we don’t ask for cookie consent.
                        </p>
                    ),
                },
                {
                    heading: 'Your rights',
                    body: (
                        <>
                            <p>
                                You can ask us for a copy of your data, to correct it, to delete it, to restrict or object to how we use it, or to
                                move it to another service. Where we rely on consent (the newsletter), you can withdraw it at any time. Contact us at{' '}
                                {email}; we reply within one month.
                            </p>
                            <p>
                                If you’re unhappy with how we handle your data, you can complain to the{' '}
                                <a href="https://ico.org.uk/make-a-complaint/">Information Commissioner’s Office</a>. See also our{' '}
                                <Link href={route('pages.terms')}>terms of sale</Link>.
                            </p>
                        </>
                    ),
                },
            ]}
            footnote="Last updated November 2026. Draft prepared for this shop's setup: have it reviewed by a legal professional before launch."
        />
    );
}
