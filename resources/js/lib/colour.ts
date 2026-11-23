import type { Product } from '@/types/shop';

/** Relative luminance of a #rrggbb colour (WCAG definition), 0 = black, 1 = white. */
export function luminance(hex: string): number {
    const [r, g, b] = [1, 3, 5].map((i) => {
        const channel = parseInt(hex.slice(i, i + 2), 16) / 255;
        return channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/**
 * The colourway that reads best as a swatch on the page background: the first
 * one that isn't near-white, falling back to the first colourway.
 */
export function featureColour(product: Product) {
    return product.colours.find((colour) => luminance(colour.hex) < 0.75) ?? product.colours[0];
}
