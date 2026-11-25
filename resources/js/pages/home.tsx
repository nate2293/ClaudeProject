import { type CollectionFigures, CollectionFiguresSection } from '@/components/shop/sections/collection-figures';
import { Hero } from '@/components/shop/sections/hero';
import ShopLayout from '@/layouts/shop-layout';
import type { Product } from '@/types/shop';

interface HomeProps {
    featuredProducts: Product[];
    collection: CollectionFigures;
}

export default function Home({ featuredProducts, collection }: HomeProps) {
    return (
        <ShopLayout title="Clothes in natural cloth" footer={false}>
            <Hero products={featuredProducts} />
            <CollectionFiguresSection collection={collection} />
        </ShopLayout>
    );
}
