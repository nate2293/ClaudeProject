import { AnimatedEcommerceProduct } from '@/components/ui/animated-e-commerce-product';
import { featureColour } from '@/lib/colour';
import { cn } from '@/lib/utils';
import type { Product, ProductBadge } from '@/types/shop';
import { router } from '@inertiajs/react';
import { Price } from './price';

export const badgeLabels: Record<ProductBadge, { label: string; className?: string }> = {
    new: { label: 'New' },
    sale: { label: 'Reduced', className: 'text-brand' },
    bestseller: { label: 'Bestseller' },
};

/** A small rectangular swing tag pinned to the corner of a product image. */
export function SwingTag({ badge, className }: { badge: ProductBadge; className?: string }) {
    const { label, className: tone } = badgeLabels[badge];

    return (
        <span className={cn('bg-background absolute top-3 left-3 z-10 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm', tone, className)}>
            {label}
        </span>
    );
}

/** Adds one unit to the bag, resolving or rejecting with the server's message. */
function addToBag(product: Product, colour: string, size: string): Promise<void> {
    return new Promise((resolve, reject) => {
        router.post(
            route('cart.lines.store'),
            { product_id: product.id, colour, size, quantity: 1 },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(new Error(errors.size ?? errors.quantity ?? errors.colour ?? 'Couldn’t add this to your bag.')),
            },
        );
    });
}

/**
 * A product in a listing: browse its colourways in place, and add it to the
 * bag without leaving the page (choosing a size in the card).
 */
export function ProductCard({ product }: { product: Product }) {
    const soldOut = product.inStock === false;
    const stock = product.stock;

    return (
        <AnimatedEcommerceProduct
            title={product.name}
            description={product.type}
            href={product.href}
            colourways={product.colours}
            image={product.image}
            initialColour={featureColour(product)?.name}
            price={<Price amount={product.price} compareAt={product.compareAtPrice} />}
            badge={
                soldOut ? (
                    <span className="bg-background text-muted-foreground absolute top-3 left-3 z-10 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm">
                        Sold out
                    </span>
                ) : (
                    product.badge && <SwingTag badge={product.badge} />
                )
            }
            sizes={product.sizes}
            soldOut={soldOut}
            isAvailable={(colour, size) => !stock || (stock.find((level) => level.colour === colour && level.size === size)?.available ?? 0) > 0}
            onAddToCart={({ colour, size }) => addToBag(product, colour, size)}
        />
    );
}
