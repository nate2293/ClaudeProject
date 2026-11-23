import type { Product } from '@/types/shop';
import { Container } from '../container';
import { ProductGrid } from '../product-grid';
import { SectionHeading } from '../section-heading';

interface FeaturedProductsProps {
    id?: string;
    title: string;
    description?: string;
    products: Product[];
    action?: { label: string; href: string };
}

export function FeaturedProducts({ id, title, description, products, action }: FeaturedProductsProps) {
    return (
        <section id={id} className="scroll-mt-20 py-16 sm:py-20">
            <Container>
                <SectionHeading title={title} description={description} action={action} />
                <ProductGrid products={products} />
            </Container>
        </section>
    );
}
