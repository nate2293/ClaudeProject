import { CartLineItem } from '@/components/shop/cart/cart-line-item';
import { OrderSummary } from '@/components/shop/cart/order-summary';
import { Container } from '@/components/shop/container';
import { EmptyState } from '@/components/shop/empty-state';
import { Button } from '@/components/ui/button';
import ShopLayout from '@/layouts/shop-layout';
import { type SharedData } from '@/types';
import type { Cart } from '@/types/shop';
import { Link, router, usePage } from '@inertiajs/react';
import { LoaderCircle, Lock, ShoppingBag } from 'lucide-react';
import { useState } from 'react';

export default function CartIndex({ cart }: { cart: Cart }) {
    const { shop } = usePage<SharedData>().props;
    const [redirecting, setRedirecting] = useState(false);

    function checkout() {
        // The server responds with an external redirect to Stripe Checkout.
        router.post(route('checkout.store'), {}, { onStart: () => setRedirecting(true), onFinish: () => setRedirecting(false) });
    }

    return (
        <ShopLayout title="Your bag">
            <Container className="py-10 sm:py-14">
                <h1 className="font-display text-4xl sm:text-5xl">Your bag</h1>

                {cart.lines.length === 0 ? (
                    <EmptyState
                        icon={ShoppingBag}
                        title="Your bag is empty"
                        description="Looks like you haven’t added anything yet. Explore the collection to find something you’ll love."
                        action={
                            <Button asChild size="lg">
                                <Link href={route('shop.index')}>Start shopping</Link>
                            </Button>
                        }
                    />
                ) : (
                    <div className="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-14">
                        <section aria-label="Items in your bag" className="lg:col-span-7">
                            <ul className="divide-y border-y">
                                {cart.lines.map((line) => (
                                    <CartLineItem key={line.key} line={line} maxQuantity={shop.maxQuantity} />
                                ))}
                            </ul>
                            <Link
                                href={route('shop.index')}
                                className="text-muted-foreground hover:text-foreground mt-6 inline-block text-sm underline underline-offset-4"
                            >
                                Continue shopping
                            </Link>
                        </section>

                        <div className="lg:col-span-5">
                            <div className="lg:sticky lg:top-24">
                                <OrderSummary
                                    subtotal={cart.subtotal}
                                    shipping={cart.shipping}
                                    total={cart.total}
                                    freeShippingThreshold={shop.freeShippingThreshold}
                                >
                                    <Button size="lg" className="h-12 w-full" onClick={checkout} disabled={redirecting || cart.hasStockIssues}>
                                        {redirecting ? <LoaderCircle className="animate-spin" /> : <Lock />}
                                        Checkout securely
                                    </Button>
                                    {cart.hasStockIssues && (
                                        <p className="text-brand mt-3 text-center text-sm font-medium">
                                            Some items have less stock than your bag asks for. Update them above to continue.
                                        </p>
                                    )}
                                    <p className="text-muted-foreground mt-3 text-center text-xs">
                                        You’ll enter delivery and payment details on Stripe’s secure checkout.
                                    </p>
                                </OrderSummary>
                            </div>
                        </div>
                    </div>
                )}
            </Container>
        </ShopLayout>
    );
}
