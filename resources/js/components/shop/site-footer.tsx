import { footerLinks, socialLinks, storeName } from '@/data/storefront';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Container } from './container';
import { ShopLogo } from './shop-logo';

export function SiteFooter() {
    const { navigation } = usePage<SharedData>().props;
    const year = new Date().getFullYear();

    return (
        <footer className="bg-muted/60 border-t">
            <Container className="grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-5">
                <div className="lg:col-span-2">
                    <ShopLogo />
                    <p className="text-muted-foreground mt-4 max-w-xs text-sm leading-relaxed">
                        Considered clothing in natural fibres, designed in London and made to be worn for years.
                    </p>
                    <ul className="mt-6 flex gap-5">
                        {socialLinks.map((link) => (
                            <li key={link.label}>
                                <a
                                    href={link.href}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-muted-foreground hover:text-foreground text-sm transition-colors"
                                >
                                    {link.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>

                {footerLinks(navigation).map((group) => (
                    <div key={group.title}>
                        <h3 className="text-sm font-semibold">{group.title}</h3>
                        <ul className="mt-4 space-y-3">
                            {group.links.map((link) => (
                                <li key={link.label}>
                                    <Link href={link.href} className="text-muted-foreground hover:text-foreground text-sm transition-colors">
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </Container>

            <div className="border-t">
                <Container className="text-muted-foreground flex flex-col gap-2 py-6 text-xs sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        © {year} {storeName}. All rights reserved.
                    </p>
                    <p>Payments securely processed by Stripe.</p>
                </Container>
            </div>
        </footer>
    );
}
