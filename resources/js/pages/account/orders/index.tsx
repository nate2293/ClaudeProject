import { OrderProgress } from '@/components/account/order-progress';
import { Pagination } from '@/components/shop/catalogue/pagination';
import { EmptyState } from '@/components/shop/empty-state';
import { ProductImage } from '@/components/shop/product-image';
import { Button } from '@/components/ui/button';
import AccountLayout, { AccountPanel } from '@/layouts/account-layout';
import type { AccountOrder, Paginated } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { Package, Truck } from 'lucide-react';

export const formatDate = (iso: string, withWeekday = false) =>
    new Date(iso).toLocaleDateString('en-GB', { ...(withWeekday && { weekday: 'long' }), day: 'numeric', month: 'long', year: 'numeric' });

const MAX_THUMBNAILS = 4;

export default function Orders({ orders }: { orders: Paginated<AccountOrder> }) {
    return (
        <AccountLayout title="My orders">
            <AccountPanel className="mb-2 flex flex-wrap items-center justify-between gap-4 p-5 sm:p-6">
                <div className="flex items-center gap-3">
                    <Package className="size-6" strokeWidth={1.5} aria-hidden />
                    <h2 className="font-display text-xl sm:text-2xl">My orders</h2>
                </div>
                <Button variant="outline" asChild>
                    <Link href={route('pages.contact')}>Need help?</Link>
                </Button>
                <p className="text-muted-foreground w-full text-sm">
                    Free returns within 30 days.{' '}
                    <Link href={route('pages.returns')} className="text-foreground underline underline-offset-4">
                        How returns work
                    </Link>
                </p>
            </AccountPanel>

            {orders.data.length === 0 ? (
                <AccountPanel>
                    <EmptyState
                        icon={Package}
                        title="No orders yet"
                        description="When you place an order, you'll be able to follow it here."
                        action={
                            <Button asChild>
                                <Link href={route('shop.index')}>Start shopping</Link>
                            </Button>
                        }
                    />
                </AccountPanel>
            ) : (
                <>
                    <p className="text-muted-foreground px-1 py-3 text-sm">
                        Showing {orders.meta.total} {orders.meta.total === 1 ? 'order' : 'orders'}
                    </p>
                    <div className="space-y-3">
                        {orders.data.map((order) => (
                            <OrderCard key={order.reference} order={order} />
                        ))}
                    </div>
                    <div className="mt-6">
                        <Pagination meta={orders.meta} links={orders.links} />
                    </div>
                </>
            )}
        </AccountLayout>
    );
}

function OrderCard({ order }: { order: AccountOrder }) {
    const shown = order.items.slice(0, MAX_THUMBNAILS);
    const more = order.items.length - shown.length;

    return (
        <AccountPanel>
            <div className="p-5 sm:p-6">
                <p className="text-muted-foreground text-xs">Order status</p>
                <h3 className="font-display mt-1 text-lg">{order.progress.title}</h3>
                <p className="text-muted-foreground mt-1 text-sm">{order.progress.detail}</p>
                <OrderProgress progress={order.progress} />
            </div>

            <div className="flex flex-col gap-5 border-t p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <ul className="flex gap-2" aria-label="Items in this order">
                    {shown.map((item) => (
                        <li key={item.id} className="w-16 sm:w-20">
                            <div className="rounded-tile aspect-[3/4] overflow-hidden">
                                <ProductImage src={item.image} swatch={item.swatch} alt={`${item.name}, ${item.colour}, size ${item.size}`} />
                            </div>
                        </li>
                    ))}
                    {more > 0 && (
                        <li className="bg-secondary text-muted-foreground rounded-tile flex aspect-[3/4] w-16 items-center justify-center text-sm sm:w-20">
                            +{more}
                        </li>
                    )}
                </ul>

                <div className="flex flex-col gap-2 sm:w-52">
                    {order.tracking?.url && (
                        <Button variant="outline" asChild>
                            <a href={order.tracking.url} target="_blank" rel="noopener noreferrer">
                                <Truck />
                                Track parcel
                            </a>
                        </Button>
                    )}
                    <Button variant={order.tracking?.url ? 'ghost' : 'outline'} asChild>
                        <Link href={order.href}>View order</Link>
                    </Button>
                </div>
            </div>

            <dl className="grid grid-cols-2 gap-x-6 gap-y-1 border-t px-5 py-4 text-xs sm:grid-cols-3 sm:px-6">
                <div>
                    <dt className="text-muted-foreground">Order no.</dt>
                    <dd className="font-medium">{order.reference}</dd>
                </div>
                <div>
                    <dt className="text-muted-foreground">Placed</dt>
                    <dd className="font-medium">{formatDate(order.placedAt)}</dd>
                </div>
                {order.returnBy && (
                    <div>
                        <dt className="text-muted-foreground">Return by</dt>
                        <dd className="font-medium">{formatDate(order.returnBy)}</dd>
                    </div>
                )}
            </dl>
        </AccountPanel>
    );
}
