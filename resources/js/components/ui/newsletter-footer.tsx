import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { type FormEvent, type ReactNode, useId, useState } from 'react';

export interface NewsletterFooterLink {
    label: string;
    href: string;
    /** Opens another site: a plain link in a new tab. */
    external?: boolean;
}

export interface NewsletterFooterColumn {
    title: string;
    links: NewsletterFooterLink[];
}

export interface NewsletterFooterProps {
    columns?: NewsletterFooterColumn[];
    /** Heading over the sign-up form. */
    newsletterTitle?: ReactNode;
    newsletterDescription?: ReactNode;
    /** Small print under the form. */
    newsletterNote?: ReactNode;
    /** Shown in place of the form once signed up. */
    successMessage?: ReactNode;
    buttonLabel?: string;
    placeholder?: string;
    /**
     * Sends the address. Resolve when it's accepted; reject with an Error whose
     * message is shown under the field.
     */
    onSubscribe?: (email: string) => Promise<void>;
    /** Bottom bar: copyright and legal lines. */
    legal?: ReactNode;
    className?: string;
    /** Keeps content in line with the rest of the page (e.g. the site's Container classes). */
    innerClassName?: string;
}

const defaultColumns: NewsletterFooterColumn[] = [
    { title: 'Product', links: ['Features', 'Pricing', 'Changelog'].map((label) => ({ label, href: '#' })) },
    { title: 'Company', links: ['About', 'Blog', 'Contact'].map((label) => ({ label, href: '#' })) },
];

const linkClass =
    'text-muted-foreground hover:text-foreground focus-visible:text-foreground focus-visible:ring-ring focus-visible:ring-offset-background inline-block rounded-sm py-1 text-sm transition-colors duration-150 outline-none focus-visible:ring-[3px] focus-visible:ring-offset-2';

/**
 * A footer with link columns on the left and a newsletter sign-up on the right,
 * over a bottom bar for the legal lines. The form announces progress, errors
 * and success to screen readers, and keeps the address if sending fails.
 */
export function NewsletterFooter({
    columns = defaultColumns,
    newsletterTitle = 'Stay in the loop',
    newsletterDescription = 'Product updates and early access to new features, delivered to your inbox.',
    newsletterNote = 'No spam. Unsubscribe anytime.',
    successMessage = 'Thanks, you’re on the list.',
    buttonLabel = 'Subscribe',
    placeholder = 'you@example.com',
    onSubscribe = () => Promise.resolve(),
    legal = `© ${new Date().getFullYear()} Acme Inc. All rights reserved.`,
    className,
    innerClassName = 'mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8',
}: NewsletterFooterProps) {
    const id = useId();
    const [email, setEmail] = useState('');
    const [status, setStatus] = useState<'idle' | 'sending' | 'done'>('idle');
    const [error, setError] = useState<string>();

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setStatus('sending');
        setError(undefined);
        try {
            await onSubscribe(email.trim());
            setEmail('');
            setStatus('done');
        } catch (reason) {
            setError(reason instanceof Error ? reason.message : 'That didn’t work. Please try again.');
            setStatus('idle');
        }
    };

    return (
        <footer className={cn('border-border bg-background border-t', className)}>
            <div className={cn('flex flex-col gap-10 py-10 lg:flex-row lg:gap-16', innerClassName)}>
                {columns.length > 0 && (
                    <nav aria-label="Footer" className="grid grid-cols-2 gap-8 sm:flex sm:gap-16">
                        {columns.map((column) => (
                            <div key={column.title}>
                                <h2 className="text-muted-foreground mb-2 text-sm">{column.title}</h2>
                                <ul className="space-y-0.5">
                                    {column.links.map((link) => (
                                        <li key={link.href + link.label}>
                                            {link.external ? (
                                                <a href={link.href} target="_blank" rel="noopener noreferrer" className={linkClass}>
                                                    {link.label}
                                                    <span className="sr-only"> (opens in a new tab)</span>
                                                </a>
                                            ) : (
                                                <Link href={link.href} className={linkClass}>
                                                    {link.label}
                                                </Link>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </nav>
                )}

                <section aria-labelledby={`${id}-title`} className="lg:ml-auto lg:max-w-sm">
                    <h2 id={`${id}-title`} className="text-sm font-medium">
                        {newsletterTitle}
                    </h2>
                    {newsletterDescription && <p className="text-muted-foreground mt-1 text-sm text-balance">{newsletterDescription}</p>}

                    <div aria-live="polite">
                        {status === 'done' ? (
                            <p role="status" className="mt-4 flex items-start gap-2 text-sm font-medium">
                                <CheckCircle2 aria-hidden className="text-primary mt-0.5 size-4 shrink-0" />
                                {successMessage}
                            </p>
                        ) : (
                            <form onSubmit={submit} className="mt-4">
                                <label htmlFor={`${id}-email`} className="sr-only">
                                    Email address
                                </label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        id={`${id}-email`}
                                        type="email"
                                        required
                                        autoComplete="email"
                                        inputMode="email"
                                        placeholder={placeholder}
                                        value={email}
                                        onChange={(event) => setEmail(event.target.value)}
                                        aria-invalid={error ? true : undefined}
                                        aria-describedby={error ? `${id}-error` : undefined}
                                        className="h-10 min-w-0 flex-1"
                                    />
                                    <Button type="submit" variant="outline" loading={status === 'sending'} loadingLabel="Signing up">
                                        {buttonLabel}
                                    </Button>
                                </div>
                                {error && (
                                    <p id={`${id}-error`} className="text-destructive mt-2 text-sm">
                                        {error}
                                    </p>
                                )}
                            </form>
                        )}
                    </div>

                    {newsletterNote && <p className="text-muted-foreground mt-3 text-xs">{newsletterNote}</p>}
                </section>
            </div>

            {legal && (
                <div className="border-border border-t">
                    <div className={cn('text-muted-foreground flex flex-col gap-1.5 py-5 text-xs sm:flex-row sm:flex-wrap sm:items-center sm:justify-between', innerClassName)}>
                        {legal}
                    </div>
                </div>
            )}
        </footer>
    );
}

export default NewsletterFooter;
