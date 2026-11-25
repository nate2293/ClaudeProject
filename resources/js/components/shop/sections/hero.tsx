import { Button } from '@/components/ui/button';
import { GradientBackground } from '@/components/ui/gradient-background';
import { type SharedData } from '@/types';
import type { Product } from '@/types/shop';
import { Link, usePage } from '@inertiajs/react';
import { Container } from '../container';
import { SwatchBook } from './swatch-book';

export function Hero({ products }: { products: Product[] }) {
    const { navigation } = usePage<SharedData>().props;

    return (
        <GradientBackground backdropBlurAmount="sm" className="pb-2 sm:pb-6">
            <section aria-labelledby="hero-heading">
                <Container className="pt-14 pb-12 sm:pt-20 sm:pb-16 lg:pt-24">
                    <h1 id="hero-heading" className="font-display max-w-4xl text-[2.5rem] leading-[1.02] text-balance sm:text-6xl lg:text-[4.5rem]">
                        Clothes in natural cloth, made to be worn for years.
                    </h1>
                    <p className="text-foreground/75 mt-6 max-w-[36rem] text-lg leading-relaxed">
                        Merino, linen, waxed cotton and selvedge denim, designed in London and made in small runs.
                    </p>

                    <div className="mt-9 flex flex-wrap gap-3">
                        {navigation.map((department, index) => (
                            <Button key={department.href} size="lg" variant={index === 0 ? 'default' : 'outline'} asChild>
                                <Link href={department.href}>Shop {department.label.toLowerCase()}</Link>
                            </Button>
                        ))}
                    </div>
                </Container>

                {products.length > 0 && <SwatchBook products={products} />}
            </section>
        </GradientBackground>
    );
}
