import { TestimonialsHorizontalSnapCarousel } from '@/components/ui/testimonials-horizontal-snap-carousel';
import type { Testimonial } from '@/types/shop';

/** Customer quotes in a swipeable, snapping row. */
export function Testimonials({ items }: { items: Testimonial[] }) {
    return (
        <TestimonialsHorizontalSnapCarousel
            className="py-16 sm:py-24"
            eyebrow="Customer stories"
            title="Worn in, and still loved."
            description="Swipe, drag or use the arrows to read what customers say after a few seasons of wear."
            items={items.map((item) => ({ quote: item.quote, author: item.author, detail: item.location, rating: item.rating }))}
            primaryCta={{ label: 'Shop the collection', href: route('shop.index') }}
            secondaryCta={{ label: 'Our story', href: route('pages.about') }}
        />
    );
}
