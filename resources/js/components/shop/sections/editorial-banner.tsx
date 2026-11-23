import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Container } from '../container';

export function EditorialBanner() {
    return (
        <section aria-labelledby="outerwear-heading" className="bg-foreground text-background">
            <Container className="grid gap-8 py-16 sm:py-20 md:grid-cols-2 md:items-end md:gap-16">
                <h2 id="outerwear-heading" className="font-display text-3xl leading-tight sm:text-5xl">
                    Coats and jackets for a British winter
                </h2>
                <div>
                    <p className="max-w-prose leading-relaxed opacity-80">
                        Waxed cotton that sheds the rain, dense wool that keeps out the wind, and quilted liners to wear under either. Each one is cut
                        to fit over a jumper.
                    </p>
                    <Button size="lg" variant="secondary" className="mt-7" asChild>
                        <Link href={route('shop.index', { type: 'Outerwear' })}>Shop outerwear</Link>
                    </Button>
                </div>
            </Container>
        </section>
    );
}
