import { cn } from '@/lib/utils';
import type { Testimonial } from '@/types/shop';
import { Star } from 'lucide-react';
import { Container } from '../container';
import { SectionHeading } from '../section-heading';

function StarRating({ rating }: { rating: number }) {
    return (
        <div className="flex gap-0.5" role="img" aria-label={`Rated ${rating} out of 5`}>
            {Array.from({ length: 5 }, (_, i) => (
                <Star key={i} className={cn('size-3.5', i < rating ? 'fill-foreground text-foreground' : 'text-border')} />
            ))}
        </div>
    );
}

export function Testimonials({ items }: { items: Testimonial[] }) {
    return (
        <section className="py-16 sm:py-24">
            <Container>
                <SectionHeading title="What customers say" />

                <div className="grid gap-12 md:grid-cols-3 md:gap-10">
                    {items.map((item) => (
                        <figure key={item.author} className="flex flex-col gap-4">
                            <StarRating rating={item.rating} />
                            <blockquote className="max-w-prose text-lg leading-relaxed">“{item.quote}”</blockquote>
                            <figcaption className="text-muted-foreground text-sm">
                                {item.author}, {item.location}
                            </figcaption>
                        </figure>
                    ))}
                </div>
            </Container>
        </section>
    );
}
