import { cn } from '@/lib/utils';

interface ProductImageProps {
    src?: string | null;
    alt: string;
    /** Cloth colour shown when there is no photo yet. */
    swatch: string;
    className?: string;
}

/**
 * Renders a product photo, or, until photography exists, a woven cloth sample
 * in the product's colour, so the page shows something true about the product.
 */
export function ProductImage({ src, alt, swatch, className }: ProductImageProps) {
    if (src) {
        return <img src={src} alt={alt} loading="lazy" className={cn('h-full w-full object-cover', className)} />;
    }

    return <div role="img" aria-label={alt} className={cn('weave h-full w-full', className)} style={{ backgroundColor: swatch }} />;
}
