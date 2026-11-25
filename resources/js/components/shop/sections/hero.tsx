import { DynamicGridGallery } from '@/components/ui/dynamic-grid-gallery';
import { GradientBackground } from '@/components/ui/gradient-background';
import { featureColour } from '@/lib/colour';
import { formatPrice } from '@/lib/format';
import { type SharedData } from '@/types';
import type { Category, Product } from '@/types/shop';
import { usePage } from '@inertiajs/react';
import { Container } from '../container';

/**
 * The landing page's opening: the gallery of featured pieces, then a quiet strip
 * of figures from the catalogue and shop settings.
 */
export function Hero({ products, categories }: { products: Product[]; categories: Category[] }) {
    const { shop } = usePage<SharedData>().props;
    const pieces = categories.reduce((total, category) => total + (category.productCount ?? 0), 0);
    const stats = [
        { value: String(pieces), label: 'Pieces in the collection' },
        { value: String(categories.length), label: 'Departments' },
        { value: formatPrice(shop.freeShippingThreshold), label: 'Free UK delivery over' },
        { value: '30 days', label: 'Free returns' },
    ];

    return (
        <GradientBackground backdropBlurAmount="sm">
            <Container className="pt-8 pb-14 sm:pt-12 sm:pb-20">
                <section aria-labelledby="hero-heading">
                    {/* The page's main heading, for screen readers and search engines. */}
                    <h1 id="hero-heading" className="sr-only">
                        Loom &amp; Lane: clothes in natural cloth, made to be worn for years
                    </h1>

                    {products.length > 0 && (
                        <div className="md:aspect-[16/9] lg:aspect-[2/1]">
                            <DynamicGridGallery
                                label="Featured pieces"
                                items={products.slice(0, 6).map((product) => ({
                                    id: product.id,
                                    title: product.name,
                                    subtitle: formatPrice(product.price),
                                    href: product.href,
                                    src: product.image,
                                    swatch: featureColour(product)?.hex ?? product.swatch,
                                    alt: product.name,
                                }))}
                                className="md:h-full"
                            />
                        </div>
                    )}

                    <dl className="mt-8 border-t pt-6">
                        <div className="mx-auto grid max-w-3xl grid-cols-2 gap-6 md:grid-cols-4 md:gap-8">
                            {stats.map((stat) => (
                                <div key={stat.label} className="flex flex-col-reverse text-center">
                                    <dt className="text-muted-foreground mt-1 text-xs">{stat.label}</dt>
                                    <dd className="font-display text-2xl tabular-nums">{stat.value}</dd>
                                </div>
                            ))}
                        </div>
                    </dl>
                </section>
            </Container>
        </GradientBackground>
    );
}
