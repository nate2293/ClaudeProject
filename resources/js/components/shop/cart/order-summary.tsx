import { Separator } from '@/components/ui/separator';
import { formatPrice } from '@/lib/format';
import { type ReactNode } from 'react';

interface OrderSummaryProps {
    subtotal: number;
    shipping: number;
    total: number;
    /** When provided, shows progress towards free delivery. */
    freeShippingThreshold?: number;
    /** ISO currency code; defaults to the shop currency. */
    currency?: string;
    /** VAT included in the total, shown beneath it. */
    vat?: { rate: string; amount: number } | null;
    children?: ReactNode;
}

export function OrderSummary({ subtotal, shipping, total, freeShippingThreshold, currency, vat, children }: OrderSummaryProps) {
    const remaining = freeShippingThreshold ? Math.max(freeShippingThreshold - subtotal, 0) : 0;
    const progress = freeShippingThreshold ? Math.min((subtotal / freeShippingThreshold) * 100, 100) : 100;

    return (
        <section aria-labelledby="summary-heading" className="bg-muted p-6 sm:p-8">
            <h2 id="summary-heading" className="font-display text-2xl">
                Order summary
            </h2>

            {freeShippingThreshold !== undefined && (
                <div className="mt-5">
                    <p className="text-sm">
                        {remaining > 0 ? (
                            <>
                                You’re <strong>{formatPrice(remaining, currency)}</strong> away from free delivery.
                            </>
                        ) : (
                            'You’ve unlocked free UK delivery.'
                        )}
                    </p>
                    <div className="bg-border mt-2 h-0.5" aria-hidden>
                        <div className="bg-foreground h-full transition-[width] motion-reduce:transition-none" style={{ width: `${progress}%` }} />
                    </div>
                </div>
            )}

            <dl className="mt-6 space-y-3 text-sm">
                <div className="flex justify-between">
                    <dt className="text-muted-foreground">Subtotal</dt>
                    <dd>{formatPrice(subtotal, currency)}</dd>
                </div>
                <div className="flex justify-between">
                    <dt className="text-muted-foreground">Delivery</dt>
                    <dd>{shipping === 0 ? 'Free' : formatPrice(shipping, currency)}</dd>
                </div>
                <Separator />
                <div className="flex justify-between text-base font-semibold">
                    <dt>Total</dt>
                    <dd>{formatPrice(total, currency)}</dd>
                </div>
                {vat && (
                    <div className="text-muted-foreground flex justify-between">
                        <dt>Includes VAT at {vat.rate}</dt>
                        <dd>{formatPrice(vat.amount, currency)}</dd>
                    </div>
                )}
            </dl>

            {children && <div className="mt-6">{children}</div>}
        </section>
    );
}
