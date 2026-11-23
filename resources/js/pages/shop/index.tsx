import { CatalogueToolbar } from '@/components/shop/catalogue/catalogue-toolbar';
import { Pagination } from '@/components/shop/catalogue/pagination';
import { Container } from '@/components/shop/container';
import { EmptyState } from '@/components/shop/empty-state';
import { PageHeader, type Crumb } from '@/components/shop/page-header';
import { ProductGrid } from '@/components/shop/product-grid';
import { Button } from '@/components/ui/button';
import ShopLayout from '@/layouts/shop-layout';
import type { CatalogueFilters, Category, Paginated, Product } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { SearchX } from 'lucide-react';

interface ShopIndexProps {
    category: Category | null;
    products: Paginated<Product>;
    types: string[];
    filters: CatalogueFilters;
}

function headingFor(category: Category | null, filters: CatalogueFilters) {
    if (category) return { title: category.name, description: category.description };
    if (filters.q) return { title: 'Search results', description: `Showing products matching “${filters.q}”.` };
    if (filters.sale) return { title: 'Sale', description: 'Last chance to buy selected pieces at reduced prices.' };
    if (filters.sort === 'newest') return { title: 'New in', description: 'The latest arrivals across every department.' };

    return { title: 'Shop all', description: 'Our full collection of considered, long-lasting clothing.' };
}

export default function ShopIndex({ category, products, types, filters }: ShopIndexProps) {
    const baseUrl = category ? category.href : route('shop.index');
    const heading = headingFor(category, filters);

    const breadcrumbs: Crumb[] = [{ label: 'Home', href: route('home') }];
    breadcrumbs.push(category ? { label: 'Shop', href: route('shop.index') } : { label: 'Shop' });
    if (category) breadcrumbs.push({ label: category.name });

    return (
        <ShopLayout title={heading.title}>
            <PageHeader title={heading.title} description={heading.description ?? undefined} breadcrumbs={breadcrumbs} />

            <Container className="py-10 sm:py-14">
                <CatalogueToolbar baseUrl={baseUrl} filters={filters} types={types} total={products.meta.total} />

                {products.data.length > 0 ? (
                    <>
                        <ProductGrid products={products.data} />
                        <Pagination meta={products.meta} links={products.links} />
                    </>
                ) : (
                    <EmptyState
                        icon={SearchX}
                        title="No products found"
                        description="Try removing a filter or searching for something else."
                        action={
                            <Button variant="outline" asChild>
                                <Link href={baseUrl}>Clear all filters</Link>
                            </Button>
                        }
                    />
                )}
            </Container>
        </ShopLayout>
    );
}
