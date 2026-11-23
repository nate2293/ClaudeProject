import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';
import { Container } from '../container';

export function NewsletterSignup() {
    const { data, setData, post, processing, errors, wasSuccessful, reset } = useForm({ email: '' });

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        post(route('newsletter.store'), { preserveScroll: true, onSuccess: () => reset() });
    }

    return (
        <section className="py-16 sm:py-24">
            <Container>
                <div className="max-w-2xl">
                    <h2 className="font-display text-3xl sm:text-4xl">Hear about new collections first</h2>
                    <p className="text-muted-foreground mt-3">
                        One email when a new collection lands, and the occasional sale. No more than twice a month.
                    </p>

                    {wasSuccessful ? (
                        <p className="mt-8 inline-flex items-center gap-2 text-sm font-medium" role="status">
                            <CheckCircle2 className="size-5" />
                            Nearly done: check your inbox and click the link to confirm.
                        </p>
                    ) : (
                        <form onSubmit={handleSubmit} className="mt-8 max-w-md">
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Label htmlFor="newsletter-email" className="sr-only">
                                    Email address
                                </Label>
                                <Input
                                    id="newsletter-email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    placeholder="you@example.com"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    aria-invalid={!!errors.email}
                                    className="bg-background h-11 flex-1"
                                />
                                <Button type="submit" size="lg" className="h-11" disabled={processing}>
                                    {processing && <LoaderCircle className="animate-spin" />}
                                    Sign up
                                </Button>
                            </div>
                            <InputError message={errors.email} className="mt-2" />
                        </form>
                    )}

                    <p className="text-muted-foreground mt-4 text-xs">
                        Unsubscribe from any email in one click. We never share your address. See our{' '}
                        <Link href={route('pages.privacy')} className="underline underline-offset-4">
                            privacy policy
                        </Link>
                        .
                    </p>
                </div>
            </Container>
        </section>
    );
}
