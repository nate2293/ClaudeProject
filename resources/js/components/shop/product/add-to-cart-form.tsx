import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import type { Product } from '@/types/shop';
import { Link, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, ShoppingBag } from 'lucide-react';
import { type FormEvent } from 'react';
import { QuantityStepper } from '../quantity-stepper';

interface AddToCartFormProps {
    product: Product;
    /** Called when the shopper picks a colour, so the page can show that colourway. */
    onColourChange?: (colour: string) => void;
}

/** Units available for a variant; untracked pages (no stock data) count as unlimited. */
function availability(product: Product, colour: string, size: string, fallback: number): number {
    if (!product.stock) return fallback;

    return product.stock.find((level) => level.colour === colour && level.size === size)?.available ?? 0;
}

export function AddToCartForm({ product, onColourChange }: AddToCartFormProps) {
    const { maxQuantity, lowStockThreshold } = usePage<SharedData>().props.shop;
    const singleSize = product.sizes.length === 1;

    const { data, setData, post, processing, errors } = useForm({
        product_id: product.id,
        colour: product.colours[0]?.name ?? '',
        size: singleSize ? product.sizes[0] : '',
        quantity: 1,
    });

    const availableFor = (size: string) => availability(product, data.colour, size, maxQuantity);
    const selectedAvailable = data.size ? availableFor(data.size) : null;
    const colourSoldOut = (colour: string) => product.sizes.every((size) => availability(product, colour, size, maxQuantity) === 0);
    const soldOut = selectedAvailable === 0 || colourSoldOut(data.colour);

    function chooseColour(colour: string) {
        // Keep the size if it's still available in the new colour; otherwise clear it.
        const keepSize = data.size && availability(product, colour, data.size, maxQuantity) > 0;
        setData((current) => ({ ...current, colour, size: keepSize || singleSize ? current.size : '', quantity: 1 }));
        onColourChange?.(colour);
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        post(route('cart.lines.store'), { preserveScroll: true });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-7">
            <fieldset>
                <legend className="mb-3 text-sm">
                    <span className="font-medium">Colour:</span> <span className="text-muted-foreground">{data.colour}</span>
                </legend>
                <div className="flex flex-wrap gap-3">
                    {product.colours.map((colour) => (
                        <label key={colour.name} className="cursor-pointer">
                            <input
                                type="radio"
                                name="colour"
                                value={colour.name}
                                checked={data.colour === colour.name}
                                onChange={() => chooseColour(colour.name)}
                                className="peer sr-only"
                            />
                            <span
                                title={colourSoldOut(colour.name) ? `${colour.name} (sold out)` : colour.name}
                                className={cn(
                                    'weave ring-offset-background peer-checked:ring-foreground peer-focus-visible:ring-ring relative block size-10 border border-black/15 ring-2 ring-transparent ring-offset-2 transition-shadow',
                                    colourSoldOut(colour.name) && 'sold-out-strike',
                                )}
                                style={{ backgroundColor: colour.hex }}
                            />
                            <span className="sr-only">
                                {colour.name}
                                {colourSoldOut(colour.name) && ' (sold out)'}
                            </span>
                        </label>
                    ))}
                </div>
                <InputError message={errors.colour} className="mt-2" />
            </fieldset>

            {!singleSize && (
                <fieldset>
                    <div className="mb-3 flex items-baseline justify-between">
                        <legend className="text-sm font-medium">Size</legend>
                        <Link
                            href={route('pages.size-guide')}
                            className="text-muted-foreground hover:text-foreground text-sm underline underline-offset-4"
                        >
                            Size guide
                        </Link>
                    </div>
                    <div className="grid grid-cols-5 gap-2">
                        {product.sizes.map((size) => {
                            const unavailable = availableFor(size) === 0;

                            return (
                                <label key={size} className={unavailable ? 'cursor-not-allowed' : 'cursor-pointer'}>
                                    <input
                                        type="radio"
                                        name="size"
                                        value={size}
                                        checked={data.size === size}
                                        onChange={() => setData((current) => ({ ...current, size, quantity: 1 }))}
                                        disabled={unavailable}
                                        className="peer sr-only"
                                    />
                                    <span
                                        className={cn(
                                            'flex h-11 items-center justify-center rounded-md border text-sm font-medium transition-colors',
                                            'hover:border-foreground/50 peer-checked:bg-foreground peer-checked:text-background peer-checked:border-foreground',
                                            'peer-focus-visible:ring-ring peer-focus-visible:ring-2 peer-focus-visible:ring-offset-2',
                                            unavailable && 'text-muted-foreground hover:border-border border-dashed line-through',
                                        )}
                                    >
                                        {size}
                                        {unavailable && <span className="sr-only"> (sold out)</span>}
                                    </span>
                                </label>
                            );
                        })}
                    </div>
                    <InputError message={errors.size} className="mt-2" />
                </fieldset>
            )}

            {selectedAvailable !== null && selectedAvailable > 0 && selectedAvailable <= lowStockThreshold && (
                <p className="text-brand text-sm font-medium" role="status">
                    Only {selectedAvailable} left in this size
                </p>
            )}

            <div className="flex gap-3">
                <QuantityStepper
                    value={data.quantity}
                    onChange={(quantity) => setData('quantity', quantity)}
                    max={Math.max(1, Math.min(maxQuantity, selectedAvailable ?? maxQuantity))}
                    disabled={soldOut}
                />
                <Button type="submit" size="lg" className="h-11 flex-1" disabled={processing || soldOut}>
                    {processing ? <LoaderCircle className="animate-spin" /> : <ShoppingBag />}
                    {soldOut ? 'Sold out' : 'Add to bag'}
                </Button>
            </div>
            <InputError message={errors.quantity ?? errors.product_id} />
        </form>
    );
}
