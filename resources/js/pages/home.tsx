import { type CollectionFigures, CollectionFiguresSection } from '@/components/shop/sections/collection-figures';
import { Hero } from '@/components/shop/sections/hero';
import { GradientBackground } from '@/components/ui/gradient-background';
import ShopLayout from '@/layouts/shop-layout';
import type { Product } from '@/types/shop';

interface HomeProps {
    featuredProducts: Product[];
    collection: CollectionFigures;
}

export default function Home({ featuredProducts, collection }: HomeProps) {
    return (
        <ShopLayout title="Clothes in natural cloth" footer={false}>
            {/* One animated background behind the whole page, down to the bottom of the window
                (and under the room left for the bottom menu on phones). */}
            <GradientBackground
                backdropBlurAmount="sm"
                className="-mb-[calc(env(safe-area-inset-bottom)+5.5rem)] min-h-[calc(100svh-4rem)] pb-[calc(env(safe-area-inset-bottom)+5.5rem)] lg:mb-0 lg:min-h-[calc(100svh-5rem)] lg:pb-16"
            >
                <Hero products={featuredProducts} />
                <CollectionFiguresSection collection={collection} />
            </GradientBackground>
        </ShopLayout>
    );
}
