import { Container } from '@/components/shop/container';
import { Breadcrumbs, type Crumb } from '@/components/shop/page-header';
import { Price } from '@/components/shop/price';
import { SwingTag } from '@/components/shop/product-card';
import { ProductImage } from '@/components/shop/product-image';
import { AddToCartForm } from '@/components/shop/product/add-to-cart-form';
import { FeaturedProducts } from '@/components/shop/sections/featured-products';
import { Separator } from '@/components/ui/separator';
import ShopLayout from '@/layouts/shop-layout';
import { formatPrice } from '@/lib/format';
import { type SharedData } from '@/types';
import type { Product } from '@/types/shop';
import { Link, usePage } from '@inertiajs/react';
import { RotateCcw, Truck } from 'lucide-react';
import { useState } from 'react';

interface ProductShowProps {
    product: Product;
    related: Product[];
}

export default function ProductShow({ product, related }: ProductShowProps) {
    const { freeShippingThreshold } = usePage<SharedData>().props.shop;
    const [colourName, setColourName] = useState(product.colours[0]?.name);
    const selectedColour = product.colours.find((colour) => colour.name === colourName);

    const breadcrumbs: Crumb[] = [
        { label: 'Home', href: route('home') },
        ...(product.category ? [{ label: product.category.name, href: product.category.href }] : []),
        { label: product.name },
    ];

    return (
        <ShopLayout title={product.name}>
            <Container className="py-8 sm:py-12">
                <Breadcrumbs items={breadcrumbs} />

                <div className="mt-6 grid gap-10 lg:grid-cols-2 lg:gap-16">
                    <div className="bg-muted relative aspect-[4/5] overflow-hidden">
                        <ProductImage
                            src={product.image}
                            alt={`${product.name} in ${selectedColour?.name ?? 'its default colour'}`}
                            swatch={selectedColour?.hex ?? product.swatch}
                            className="transition-colors duration-300 motion-reduce:transition-none"
                        />
                        {product.badge && <SwingTag badge={product.badge} className="left-5 pt-4 text-sm" />}
                    </div>

                    <div className="lg:sticky lg:top-24 lg:self-start">
                        <h1 className="font-display text-3xl leading-tight sm:text-[2.625rem]">{product.name}</h1>
                        <Price amount={product.price} compareAt={product.compareAtPrice} className="mt-4 text-lg" />

                        <p className="text-muted-foreground mt-6 max-w-prose leading-relaxed">{product.description}</p>

                        <Separator className="my-8" />

                        <AddToCartForm key={product.id} product={product} onColourChange={setColourName} />

                        <ul className="mt-8 space-y-3 text-sm">
                            <li className="flex items-center gap-3">
                                <Truck className="text-muted-foreground size-4" />
                                <span>
                                    Free UK delivery over {formatPrice(freeShippingThreshold)}.{' '}
                                    <Link href={route('pages.delivery')} className="underline underline-offset-4">
                                        Delivery info
                                    </Link>
                                </span>
                            </li>
                            <li className="flex items-center gap-3">
                                <RotateCcw className="text-muted-foreground size-4" />
                                <span>
                                    Free returns within 30 days.{' '}
                                    <Link href={route('pages.returns')} className="underline underline-offset-4">
                                        Returns policy
                                    </Link>
                                </span>
                            </li>
                        </ul>

                        {product.materials && (
                            <div className="mt-8 border-t pt-6">
                                <h2 className="text-sm font-semibold">Materials &amp; care</h2>
                                <p className="text-muted-foreground mt-2 text-sm">{product.materials}</p>
                                <p className="text-muted-foreground mt-1 text-sm">Cool wash or dry clean. Dry flat in shade.</p>
                            </div>
                        )}
                    </div>
                </div>
            </Container>

            {related.length > 0 && (
                <div className="border-t">
                    <FeaturedProducts
                        title="You may also like"
                        products={related}
                        action={product.category ? { label: `More ${product.category.name.toLowerCase()}`, href: product.category.href } : undefined}
                    />
                </div>
            )}
        </ShopLayout>
    );
}
