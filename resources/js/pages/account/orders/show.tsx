import { OrderProgress } from '@/components/account/order-progress';
import { OrderSummary } from '@/components/shop/cart/order-summary';
import { ProductImage } from '@/components/shop/product-image';
import { Button } from '@/components/ui/button';
import AccountLayout, { AccountPanel } from '@/layouts/account-layout';
import { formatPrice } from '@/lib/format';
import type { AccountOrder } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { ChevronLeft, Truck } from 'lucide-react';
import { formatDate } from './index';

export default function OrderDetails({ order }: { order: AccountOrder }) {
    return (
        <AccountLayout title={`Order ${order.reference}`}>
            <Link href={route('account.orders')} className="text-muted-foreground hover:text-foreground mb-3 inline-flex items-center gap-1 text-sm">
                <ChevronLeft className="size-4" aria-hidden />
                All orders
            </Link>

            <div className="space-y-3">
                <AccountPanel className="p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 className="font-display text-xl sm:text-2xl">Order {order.reference}</h2>
                            <p className="text-muted-foreground mt-1 text-sm">Placed {formatDate(order.placedAt, true)}</p>
                        </div>
                        {order.tracking?.url && (
                            <Button asChild>
                                <a href={order.tracking.url} target="_blank" rel="noopener noreferrer">
                                    <Truck />
                                    Track parcel
                                </a>
                            </Button>
                        )}
                    </div>
                    <div className="mt-5 border-t pt-5">
                        <p className="font-display text-lg">{order.progress.title}</p>
                        <p className="text-muted-foreground mt-1 text-sm">{order.progress.detail}</p>
                        <OrderProgress progress={order.progress} />
                        {order.tracking?.number && (
                            <p className="mt-4 text-sm">
                                Tracking number <span className="font-medium">{order.tracking.number}</span>
                                {order.tracking.carrier && <> with {order.tracking.carrier}</>}
                            </p>
                        )}
                    </div>
                </AccountPanel>

                <div className="grid items-start gap-3 xl:grid-cols-[1fr_20rem]">
                    <AccountPanel>
                        <h3 className="border-b px-5 py-4 font-semibold sm:px-6">{order.items.reduce((n, item) => n + item.quantity, 0)} items</h3>
                        <ul className="divide-y">
                            {order.items.map((item) => (
                                <li key={item.id} className="flex gap-4 p-5 sm:px-6">
                                    <div className="rounded-tile aspect-[3/4] w-20 shrink-0 overflow-hidden">
                                        <ProductImage src={item.image} swatch={item.swatch} alt={item.name} />
                                    </div>
                                    <div className="flex flex-1 justify-between gap-4 text-sm">
                                        <div>
                                            {item.href ? (
                                                <Link href={item.href} className="font-medium hover:underline">
                                                    {item.name}
                                                </Link>
                                            ) : (
                                                <p className="font-medium">{item.name}</p>
                                            )}
                                            <p className="text-muted-foreground mt-1">
                                                {item.colour}, size {item.size}
                                            </p>
                                            <p className="text-muted-foreground">Qty {item.quantity}</p>
                                        </div>
                                        <p className="font-medium">{formatPrice(item.total, order.currency)}</p>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </AccountPanel>

                    <div className="space-y-3">
                        <OrderSummary
                            subtotal={order.subtotal}
                            shipping={order.shipping}
                            total={order.total}
                            currency={order.currency}
                            vat={order.vat}
                        >
                            {order.refunded > 0 && (
                                <p className="flex justify-between text-sm">
                                    <span>Refunded</span>
                                    <span>{formatPrice(order.refunded, order.currency)}</span>
                                </p>
                            )}
                        </OrderSummary>

                        {order.deliverTo && order.deliverTo.lines.length > 0 && (
                            <AccountPanel className="p-5 text-sm">
                                <h3 className="font-semibold">Delivering to</h3>
                                <address className="text-muted-foreground mt-2 not-italic">
                                    {order.deliverTo.name && <span className="text-foreground block">{order.deliverTo.name}</span>}
                                    {order.deliverTo.lines.map((line) => (
                                        <span key={line} className="block">
                                            {line}
                                        </span>
                                    ))}
                                </address>
                            </AccountPanel>
                        )}

                        <AccountPanel className="p-5 text-sm">
                            <h3 className="font-semibold">Something not right?</h3>
                            <p className="text-muted-foreground mt-2">
                                Free returns within 30 days. For anything else,{' '}
                                <Link href={route('pages.contact')} className="text-foreground underline underline-offset-4">
                                    contact us
                                </Link>{' '}
                                and quote {order.reference}.
                            </p>
                        </AccountPanel>
                    </div>
                </div>
            </div>
        </AccountLayout>
    );
}
