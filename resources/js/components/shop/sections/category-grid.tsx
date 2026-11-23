import type { Category } from '@/types/shop';
import { Link } from '@inertiajs/react';
import { Container } from '../container';

/** The three departments as a plain list: name, what's in it and how many pieces. */
export function CategoryGrid({ categories }: { categories: Category[] }) {
    return (
        <section aria-labelledby="departments-heading" className="py-16 sm:py-20">
            <Container>
                <h2 id="departments-heading" className="font-display mb-8 text-3xl sm:text-4xl">
                    Shop by department
                </h2>

                <ul className="border-t">
                    {categories.map((category) => (
                        <li key={category.slug} className="border-b">
                            <Link
                                href={category.href}
                                className="group grid items-baseline gap-x-8 gap-y-1 py-6 sm:grid-cols-[minmax(0,16rem)_1fr_auto] sm:py-8"
                            >
                                <span className="font-display text-2xl decoration-2 underline-offset-[6px] group-hover:underline sm:text-3xl">
                                    {category.name}
                                </span>
                                <span className="text-muted-foreground">{category.description}</span>
                                {category.productCount !== undefined && (
                                    <span className="text-muted-foreground text-sm tabular-nums">
                                        {category.productCount} {category.productCount === 1 ? 'piece' : 'pieces'}
                                    </span>
                                )}
                            </Link>
                        </li>
                    ))}
                </ul>
            </Container>
        </section>
    );
}
