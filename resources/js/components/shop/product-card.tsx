import { featureColour } from '@/lib/colour';
import { cn } from '@/lib/utils';
import type { Product, ProductBadge } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { ColourChips } from './colour-chips';
import { Price } from './price';
import { ProductImage } from './product-image';

export const badgeLabels: Record<ProductBadge, { label: string; className?: string }> = {
    new: { label: 'New' },
    sale: { label: 'Reduced', className: 'text-brand' },
    bestseller: { label: 'Bestseller' },
};

/** A small rectangular swing tag pinned to the corner of a product image. */
export function SwingTag({ badge, className }: { badge: ProductBadge; className?: string }) {
    const { label, className: tone } = badgeLabels[badge];

    return <span className={cn('bg-background absolute top-0 left-3 px-2 pt-3 pb-1.5 text-xs font-semibold', tone, className)}>{label}</span>;
}

export function ProductCard({ product }: { product: Product }) {
    const soldOut = product.inStock === false;

    return (
        <article className="group relative flex flex-col">
            <div className={cn('bg-muted relative aspect-[3/4] overflow-hidden', soldOut && 'opacity-60')}>
                <ProductImage src={product.image} alt={product.name} swatch={featureColour(product)?.hex ?? product.swatch} />
                {soldOut ? (
                    <span className="bg-background text-muted-foreground absolute top-0 left-3 px-2 pt-3 pb-1.5 text-xs font-semibold">Sold out</span>
                ) : (
                    product.badge && <SwingTag badge={product.badge} />
                )}
            </div>

            <div className="mt-3 flex flex-1 flex-col gap-0.5">
                <h3 className="text-[0.9375rem] leading-snug font-medium">
                    <Link href={product.href} className="decoration-1 underline-offset-4 group-hover:underline after:absolute after:inset-0">
                        {product.name}
                    </Link>
                </h3>
                <p className="text-muted-foreground text-sm">{product.type}</p>
                <Price amount={product.price} compareAt={product.compareAtPrice} className="mt-1" />
                <ColourChips colours={product.colours} className="mt-2.5" />
            </div>
        </article>
    );
}
