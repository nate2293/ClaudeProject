import { CenteredShimmerTextHeadlineHero } from '@/components/ui/centered-shimmer-text-headline-hero';
import { DynamicGridGallery } from '@/components/ui/dynamic-grid-gallery';
import { GradientBackground } from '@/components/ui/gradient-background';
import { featureColour } from '@/lib/colour';
import { formatPrice } from '@/lib/format';
import { type SharedData } from '@/types';
import type { Category, Product } from '@/types/shop';
import { usePage } from '@inertiajs/react';
import { Container } from '../container';

/**
 * The landing page's opening, in order of importance: the headline and one main
 * action, then the gallery of featured pieces as the visual focus, then a quiet
 * strip of figures. Figures come from the catalogue and shop settings.
 */
export function Hero({ products, categories }: { products: Product[]; categories: Category[] }) {
    const { shop } = usePage<SharedData>().props;
    const pieces = categories.reduce((total, category) => total + (category.productCount ?? 0), 0);

    return (
        <GradientBackground backdropBlurAmount="sm">
            <Container className="pt-12 pb-14 sm:pt-16 sm:pb-20">
                <CenteredShimmerTextHeadlineHero
                    bare
                    className="max-w-7xl"
                    announcement={{ label: 'New in', text: 'Coats and jackets for winter', href: route('shop.index', { type: 'Outerwear' }) }}
                    headline="Clothes in natural cloth,"
                    shimmerText="made to be worn for years"
                    description="Merino, linen, waxed cotton and selvedge denim, designed in London and made in small runs."
                    primaryCta={{ label: 'Shop the collection', href: route('shop.index') }}
                    secondaryCta={{ label: 'Our story', href: route('pages.about') }}
                    note={undefined}
                    stats={[
                        { value: String(pieces), label: 'Pieces in the collection' },
                        { value: String(categories.length), label: 'Departments' },
                        { value: formatPrice(shop.freeShippingThreshold), label: 'Free UK delivery over' },
                        { value: '30 days', label: 'Free returns' },
                    ]}
                >
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
                </CenteredShimmerTextHeadlineHero>
            </Container>
        </GradientBackground>
    );
}
