import { CategoryGrid } from '@/components/shop/sections/category-grid';
import { EditorialBanner } from '@/components/shop/sections/editorial-banner';
import { Hero } from '@/components/shop/sections/hero';
import { NewsletterSignup } from '@/components/shop/sections/newsletter-signup';
import { Testimonials } from '@/components/shop/sections/testimonials';
import { testimonials } from '@/data/storefront';
import ShopLayout from '@/layouts/shop-layout';
import type { Category, Product } from '@/types/shop';

interface HomeProps {
    categories: Category[];
    featuredProducts: Product[];
}

export default function Home({ categories, featuredProducts }: HomeProps) {
    return (
        <ShopLayout title="Clothes in natural cloth">
            <Hero products={featuredProducts} />
            <CategoryGrid categories={categories} />
            <EditorialBanner />
            <Testimonials items={testimonials} />
            <NewsletterSignup />
        </ShopLayout>
    );
}
