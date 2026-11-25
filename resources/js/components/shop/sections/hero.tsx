import { CenteredShimmerTextHeadlineHero } from '@/components/ui/centered-shimmer-text-headline-hero';
import { GradientBackground } from '@/components/ui/gradient-background';
import { formatPrice } from '@/lib/format';
import { type SharedData } from '@/types';
import type { Category, Product } from '@/types/shop';
import { usePage } from '@inertiajs/react';
import { Container } from '../container';
import { SwatchBook } from './swatch-book';

/**
 * The home page's opening: a frosted shimmer-headline card over the flowing
 * gradient, then the swatch book of featured pieces. Figures come from the
 * catalogue and shop settings, so they stay true.
 */
export function Hero({ products, categories }: { products: Product[]; categories: Category[] }) {
    const { shop } = usePage<SharedData>().props;
    const pieces = categories.reduce((total, category) => total + (category.productCount ?? 0), 0);

    return (
        <GradientBackground backdropBlurAmount="sm" className="pb-2 sm:pb-6">
            <Container className="pt-10 pb-12 sm:pt-14 sm:pb-16">
                <CenteredShimmerTextHeadlineHero
                    announcement={{ label: 'New in', text: 'Coats and jackets for winter', href: route('shop.index', { type: 'Outerwear' }) }}
                    headline="Clothes in natural cloth,"
                    shimmerText="made to be worn for years"
                    description="Merino, linen, waxed cotton and selvedge denim, designed in London and made in small runs."
                    primaryCta={{ label: 'Shop the collection', href: route('shop.index') }}
                    secondaryCta={{ label: 'Our story', href: route('pages.about') }}
                    note={`Free UK delivery over ${formatPrice(shop.freeShippingThreshold)} · Free returns within 30 days`}
                    stats={[
                        { value: String(pieces), label: 'Pieces in the collection' },
                        { value: String(categories.length), label: 'Departments' },
                        { value: formatPrice(shop.freeShippingThreshold), label: 'Free UK delivery over' },
                        { value: '30 days', label: 'Free returns' },
                    ]}
                />
            </Container>

            {products.length > 0 && <SwatchBook products={products} />}
        </GradientBackground>
    );
}
