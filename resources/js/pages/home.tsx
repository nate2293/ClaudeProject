import { Hero } from '@/components/shop/sections/hero';
import ShopLayout from '@/layouts/shop-layout';
import type { Product } from '@/types/shop';

interface HomeProps {
    featuredProducts: Product[];
}

export default function Home({ featuredProducts }: HomeProps) {
    return (
        <ShopLayout title="Clothes in natural cloth" footer={false}>
            <Hero products={featuredProducts} />
        </ShopLayout>
    );
}
