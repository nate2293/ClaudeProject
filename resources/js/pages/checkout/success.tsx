import { OrderSummary } from '@/components/shop/cart/order-summary';
import { Container } from '@/components/shop/container';
import { Button } from '@/components/ui/button';
import ShopLayout from '@/layouts/shop-layout';
import { formatPrice } from '@/lib/format';
import type { Order } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { CheckCircle2, Clock } from 'lucide-react';

export default function CheckoutSuccess({ order }: { order: Order }) {
    const paid = order.status === 'paid';
    const StatusIcon = paid ? CheckCircle2 : Clock;

    return (
        <ShopLayout title="Thank you for your order">
            <Container className="max-w-3xl py-14 sm:py-20">
                <div className="text-center">
                    <StatusIcon className="text-brand mx-auto size-12" strokeWidth={1.5} />
                    <h1 className="font-display mt-6 text-4xl sm:text-5xl">{paid ? 'Thank you for your order' : 'Your order is being processed'}</h1>
                    <p className="text-muted-foreground mt-4">
                        {paid
                            ? 'Your payment was successful and we’re getting your order ready.'
                            : 'We’re waiting for your bank to confirm the payment. Your bag is kept until it does. Refresh this page to see the latest status.'}
                    </p>
                    <p className="mt-6 text-sm">
                        Order reference <span className="bg-muted rounded-full px-3 py-1 font-mono font-medium">{order.reference}</span>
                    </p>
                </div>

                <div className="mt-12 grid gap-8 md:grid-cols-5">
                    <section aria-labelledby="items-heading" className="md:col-span-3">
                        <h2 id="items-heading" className="font-display text-2xl">
                            Items
                        </h2>
                        <ul className="mt-4 divide-y border-y">
                            {order.items.map((item) => (
                                <li key={item.id} className="flex justify-between gap-4 py-4 text-sm">
                                    <div>
                                        <p className="font-medium">{item.name}</p>
                                        <p className="text-muted-foreground mt-0.5">
                                            {item.colour}, size {item.size}, quantity {item.quantity}
                                        </p>
                                    </div>
                                    <p className="font-medium">{formatPrice(item.total, order.currency)}</p>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <div className="md:col-span-2">
                        <OrderSummary
                            subtotal={order.subtotal}
                            shipping={order.shipping}
                            total={order.total}
                            currency={order.currency}
                            vat={order.vat}
                        />
                    </div>
                </div>

                <div className="mt-12 text-center">
                    <Button asChild size="lg">
                        <Link href={route('shop.index')}>Continue shopping</Link>
                    </Button>
                </div>
            </Container>
        </ShopLayout>
    );
}
