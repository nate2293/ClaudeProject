import { NewsletterFooter } from '@/components/ui/newsletter-footer';
import { footerLinks, storeName } from '@/data/storefront';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';

/** Sign up through the double opt-in endpoint: the address joins only after the emailed link is clicked. */
function subscribe(email: string) {
    return new Promise<void>((resolve, reject) => {
        router.post(
            route('newsletter.store'),
            { email },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(new Error(errors.email ?? 'That didn’t work. Please try again.')),
            },
        );
    });
}

/** The storefront footer: Shop / Help / About links, the newsletter sign-up, and the legal lines. */
export function SiteFooter() {
    const { navigation, shop } = usePage<SharedData>().props;
    const { legalName, companyNumber, vatNumber } = shop.business;

    // Registered company details, as UK company websites must show them, once configured.
    const registration = [companyNumber && `Company no. ${companyNumber}`, vatNumber && `VAT no. ${vatNumber}`].filter(Boolean).join(', ');

    return (
        <NewsletterFooter
            columns={footerLinks(navigation)}
            newsletterTitle="Hear about new collections first"
            newsletterDescription="One email when a new collection lands, and the occasional sale. No more than twice a month."
            successMessage="Nearly done: check your inbox and click the link to confirm."
            buttonLabel="Sign up"
            onSubscribe={subscribe}
            newsletterNote={
                <>
                    Unsubscribe from any email in one click. We never share your address. See our{' '}
                    <Link href={route('pages.privacy')} className="hover:text-foreground underline underline-offset-4">
                        privacy policy
                    </Link>
                    .
                </>
            }
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
