import { AccountSectionsBar, AccountSidebar } from '@/components/account/account-sidebar';
import { Container } from '@/components/shop/container';
import ShopLayout from '@/layouts/shop-layout';
import { type ReactNode } from 'react';

/**
 * The signed-in customer's account: the shop's header and footer, a sidebar of
 * account sections, and the current section on a light chambray ground.
 */
export default function AccountLayout({ title, children }: { title?: string; children: ReactNode }) {
    return (
        <ShopLayout title={title}>
            <div className="bg-secondary/50 border-b">
                <Container className="py-8 sm:py-12">
                    <h1 className="font-display mb-4 text-2xl sm:mb-6 sm:text-3xl">My account</h1>
                    <AccountSectionsBar />
                    <div className="grid items-start gap-6 lg:grid-cols-[17rem_1fr] lg:gap-8">
                        <div className="hidden lg:block">
                            <AccountSidebar />
                        </div>
                        <div className="min-w-0">{children}</div>
                    </div>
                </Container>
            </div>
        </ShopLayout>
    );
}

/** A white panel inside the account area. */
export function AccountPanel({ children, className = '' }: { children: ReactNode; className?: string }) {
    return <section className={`bg-background rounded-card overflow-hidden border ${className}`}>{children}</section>;
}
