import { featureColour } from '@/lib/colour';
import { formatPrice } from '@/lib/format';
import type { Product } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { type CSSProperties } from 'react';

/**
 * The homepage's signature element: this season's pieces laid out like a mill
 * swatch book, one cut sample of cloth per product, each linking to it.
 */
export function SwatchBook({ products }: { products: Product[] }) {
    return (
        <div className="overflow-x-auto overscroll-x-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <ul className="mx-auto flex w-max snap-x snap-mandatory gap-1.5 px-4 sm:px-6 lg:grid lg:w-full lg:max-w-7xl lg:auto-cols-fr lg:grid-flow-col lg:px-8">
                {products.map((product, index) => {
                    const colour = featureColour(product);
                    // Alternate heights so the samples read as a fanned book rather than a grid.
                    const height =
                        index % 3 === 1 ? 'h-[22rem] sm:h-[26rem]' : index % 3 === 2 ? 'h-[19rem] sm:h-[23rem]' : 'h-[20.5rem] sm:h-[24.5rem]';

                    return (
                        <li
                            key={product.id}
                            className="w-40 shrink-0 snap-start self-end motion-safe:animate-[swatch-in_700ms_cubic-bezier(0.2,0.7,0.2,1)_both] sm:w-48 lg:w-auto"
                            style={{ animationDelay: `${index * 70}ms` } as CSSProperties}
                        >
                            <Link
                                href={product.href}
                                className={`pinked-top weave group relative flex ${height} flex-col justify-end p-2 transition-transform duration-300 hover:-translate-y-2 focus-visible:-translate-y-2 focus-visible:outline-offset-4 motion-reduce:transition-none`}
                                style={{ backgroundColor: colour?.hex ?? product.swatch }}
                            >
                                <span className="bg-background text-foreground block px-2.5 py-2 text-xs leading-snug shadow-[0_1px_0_rgb(0_0_0/0.08)]">
                                    <span className="block font-semibold">{colour?.name}</span>
                                    <span className="text-muted-foreground block group-hover:underline">{product.name}</span>
                                    <span className="mt-1 block tabular-nums">{formatPrice(product.price)}</span>
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
