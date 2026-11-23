import { formatPrice } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { CartLine } from '@/types/shop';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ProductImage } from '../product-image';
import { QuantityStepper } from '../quantity-stepper';

interface CartLineItemProps {
    line: CartLine;
    maxQuantity: number;
}

export function CartLineItem({ line, maxQuantity }: CartLineItemProps) {
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string>();
    const options = { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) };
    const soldOut = line.available === 0;
    const short = !soldOut && line.quantity > line.available;

    function updateQuantity(quantity: number) {
        setError(undefined);
        router.patch(route('cart.lines.update', line.key), { quantity }, { ...options, onError: (errors) => setError(errors.quantity) });
    }

    function remove() {
        router.delete(route('cart.lines.destroy', line.key), options);
    }

    return (
        <li className={cn('flex gap-4 py-6 transition-opacity sm:gap-6', busy && 'opacity-60')}>
            <Link href={line.product.href} className="w-24 shrink-0 overflow-hidden rounded-lg sm:w-32">
                <div className="aspect-[3/4]">
                    <ProductImage src={line.product.image} alt={line.product.name} swatch={line.colourHex ?? line.product.swatch} />
                </div>
            </Link>

            <div className="flex flex-1 flex-col">
                <div className="flex justify-between gap-4">
                    <div>
                        <h3 className="font-medium">
                            <Link href={line.product.href} className="hover:underline">
                                {line.product.name}
                            </Link>
                        </h3>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {line.colour}, size {line.size}
                        </p>
                        <p className="text-muted-foreground mt-1 text-sm">{formatPrice(line.unitPrice)} each</p>
                        {(soldOut || short || error) && (
                            <p className="text-brand mt-2 text-sm font-medium" role="alert">
                                {error ??
                                    (soldOut
                                        ? 'Sold out. Please remove it to check out.'
                                        : `Only ${line.available} left. Please lower the quantity to check out.`)}
                            </p>
                        )}
                    </div>
                    <p className="font-medium">{formatPrice(line.total)}</p>
                </div>

                <div className="mt-auto flex items-center justify-between pt-4">
                    <QuantityStepper
                        size="sm"
                        value={line.quantity}
                        onChange={updateQuantity}
                        // Lowering is always allowed; raising only as far as the stock goes.
                        max={Math.max(1, Math.min(maxQuantity, Math.max(line.available, line.quantity)))}
                        disabled={busy || soldOut}
                        label={`Quantity for ${line.product.name}`}
                    />
                    <button
                        type="button"
                        onClick={remove}
                        disabled={busy}
                        className="text-muted-foreground hover:text-foreground text-sm underline underline-offset-4"
                    >
                        Remove
                    </button>
                </div>
            </div>
        </li>
    );
}
