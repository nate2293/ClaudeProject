import { DynamicGridGallery } from '@/components/ui/dynamic-grid-gallery';
import { GradientBackground } from '@/components/ui/gradient-background';
import { featureColour } from '@/lib/colour';
import { formatPrice } from '@/lib/format';
import type { Product } from '@/types/shop';
import { Container } from '../container';

/**
 * The landing page's opening: the gallery of featured pieces.
 */
export function Hero({ products }: { products: Product[] }) {
    return (
        <GradientBackground backdropBlurAmount="sm">
            <Container className="pt-8 pb-12 sm:pt-12 sm:pb-16">
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
                </section>
            </Container>
        </GradientBackground>
    );
}
