import { EditorialBanner } from '@/components/shop/sections/editorial-banner';
import { Hero } from '@/components/shop/sections/hero';
import { Testimonials } from '@/components/shop/sections/testimonials';
import { testimonials } from '@/data/storefront';
import ShopLayout from '@/layouts/shop-layout';
import type { Product } from '@/types/shop';

interface HomeProps {
    featuredProducts: Product[];
}

export default function Home({ featuredProducts }: HomeProps) {
    return (
        <ShopLayout title="Clothes in natural cloth" footer={false}>
            <Hero products={featuredProducts} />
            <EditorialBanner />
            <Testimonials items={testimonials} />
        </ShopLayout>
    );
}
