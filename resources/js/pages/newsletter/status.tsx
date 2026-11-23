import { Container } from '@/components/shop/container';
import { Button } from '@/components/ui/button';
import ShopLayout from '@/layouts/shop-layout';
import { Link } from '@inertiajs/react';

type State = 'confirmed' | 'unsubscribed' | 'invalid';

const copy: Record<State, { title: string; body: string }> = {
    confirmed: {
        title: 'You’re subscribed',
        body: 'Thanks for confirming. You’ll hear from us when a new collection lands. Every email has a one-click unsubscribe link.',
    },
    unsubscribed: {
        title: 'You’ve been unsubscribed',
        body: 'You won’t receive any more newsletters from us. Changed your mind? You can sign up again at the bottom of any page.',
    },
    invalid: {
        title: 'This link has expired',
        body: 'Confirmation links last 7 days. Sign up again at the bottom of any page and we’ll send you a new one.',
    },
};

export default function NewsletterStatus({ state }: { state: State }) {
    const { title, body } = copy[state];

    return (
        <ShopLayout title={title}>
            <Container className="max-w-xl py-20 text-center sm:py-28">
                <h1 className="font-display text-3xl sm:text-4xl">{title}</h1>
                <p className="text-muted-foreground mt-4 leading-relaxed">{body}</p>
                <Button asChild size="lg" className="mt-8">
                    <Link href={route('home')}>Continue shopping</Link>
                </Button>
            </Container>
        </ShopLayout>
    );
}
