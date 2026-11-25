import { DynamicGridGallery } from '@/components/ui/dynamic-grid-gallery';
import { GradientBackground } from '@/components/ui/gradient-background';
import { TypewriterHero } from '@/components/ui/typewriter-hero';
import { featureColour } from '@/lib/colour';
import { formatPrice } from '@/lib/format';
import type { Product } from '@/types/shop';
import { Container } from '../container';

/**
 * The landing page's opening: the gallery of featured pieces, then the
 * typewriter headline and calls to action.
 */
export function Hero({ products }: { products: Product[] }) {
    return (
        <GradientBackground backdropBlurAmount="sm">
            <Container className="pt-8 pb-12 sm:pt-12 sm:pb-16">
                <section aria-labelledby="hero-heading">
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

                    <TypewriterHero
                        className={products.length > 0 ? 'mt-6' : undefined}
                        headingId="hero-heading"
                        title="Loom & Lane: clothes in natural cloth, made to last"
                        prefix="Clothes made to"
                        words={['last', 'layer', 'be worn', 'be mended']}
                        description="Wool, linen and waxed cotton, cut to be worn for years rather than seasons."
                        announcement={{ label: 'New in', detail: 'See the latest pieces', href: route('shop.index', { sort: 'newest' }) }}
                        primaryAction={{ label: 'Shop the collection', href: route('shop.index') }}
                        secondaryAction={{ label: 'Our story', href: route('pages.about') }}
                    />
                </section>
            </Container>
        </GradientBackground>
    );
}
