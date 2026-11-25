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

/** WCAG contrast ratio between two #rrggbb colours (1 to 21). */
export function contrastRatio(a: string, b: string): number {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (light + 0.05) / (dark + 0.05);
}

/** Mix a #rrggbb colour towards white (amount > 0) or black (amount < 0), amount in -1..1. */
export function mix(hex: string, amount: number): string {
    const target = amount > 0 ? 255 : 0;
    const t = Math.abs(amount);
    const channels = [1, 3, 5].map((i) => {
        const value = parseInt(hex.slice(i, i + 2), 16);
        return Math.round(value + (target - value) * t)
            .toString(16)
            .padStart(2, '0');
    });

    return `#${channels.join('')}`;
}

/**
 * The colourway's own hue, darkened (or lightened) just enough to be readable
 * as text on `background`: so a cream colourway still gets legible ink.
 */
export function readableInk(hex: string, background: string, minimum = 4.5): string {
    const direction = luminance(background) > 0.5 ? -1 : 1;

    for (let step = 0; step <= 20; step++) {
        const candidate = mix(hex, direction * step * 0.05);
        if (contrastRatio(candidate, background) >= minimum) return candidate;
    }

    return direction < 0 ? '#000000' : '#ffffff';
}
