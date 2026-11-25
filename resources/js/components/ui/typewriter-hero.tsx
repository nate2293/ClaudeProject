import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight } from 'lucide-react';
import { type ReactNode, useEffect, useState } from 'react';

export interface TypewriterHeroAction {
    label: string;
    href: string;
}

export interface TypewriterHeroProps {
    /** Words before the rotating one, e.g. "Clothes made to". */
    prefix?: string;
    /** Rotating words, typed and deleted in turn. */
    words?: string[];
    /** The heading as screen readers and search engines read it (the animation is hidden from them). */
    title?: string;
    description?: ReactNode;
    /** The small announcement pill above the heading. Leave out to hide it. */
    announcement?: { label: string; detail?: string; href: string };
    primaryAction?: TypewriterHeroAction;
    secondaryAction?: TypewriterHeroAction;
    /** Optional line under the buttons (only use with real figures). */
    footnote?: ReactNode;
    /** Heading level id, for aria-labelledby on the surrounding section. */
    headingId?: string;
    /** Typing speed in ms per letter; deleting runs at twice the speed. */
    typeSpeed?: number;
    /** How long a finished word stays, in ms. */
    holdFor?: number;
    className?: string;
}

function usePrefersReducedMotion() {
    const [reduced, setReduced] = useState(false);

    useEffect(() => {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        const update = () => setReduced(query.matches);
        update();
        query.addEventListener('change', update);
        return () => query.removeEventListener('change', update);
    }, []);

    return reduced;
}

/** Types a word, holds it, deletes it, then moves to the next. */
function useTypewriter(words: string[], typeSpeed: number, holdFor: number, enabled: boolean) {
    const [index, setIndex] = useState(0);
    const [length, setLength] = useState(words[0]?.length ?? 0);
    const [deleting, setDeleting] = useState(false);

    useEffect(() => {
        if (!enabled || words.length < 2) return;

        const word = words[index % words.length];
        let delay = deleting ? typeSpeed / 2 : typeSpeed;
        let next: () => void;

        if (!deleting && length === word.length) {
            delay = holdFor;
            next = () => setDeleting(true);
        } else if (deleting && length === 0) {
            delay = typeSpeed * 3;
            next = () => {
                setDeleting(false);
                setIndex((i) => (i + 1) % words.length);
            };
        } else {
            next = () => setLength((l) => l + (deleting ? -1 : 1));
        }

        const timer = window.setTimeout(next, delay);
        return () => window.clearTimeout(timer);
    }, [words, index, length, deleting, typeSpeed, holdFor, enabled]);

    const word = words[index % words.length] ?? '';
    return enabled ? word.slice(0, length) : (words[0] ?? '');
}

const defaultWords = ['faster', 'better', 'together'];

/**
 * A centred hero: an optional announcement pill, a heading whose last word is
 * typed out and swapped in turn behind a blinking cursor, a short description
 * and two calls to action. Screen readers get the plain `title` instead of the
 * changing text; reduced-motion users see the first word, still.
 */
export function TypewriterHero({
    prefix = 'Build',
    words = defaultWords,
    title,
    description = 'The platform that adapts to your workflow, with tools that stay out of your way.',
    announcement,
    primaryAction = { label: 'Get started', href: '#' },
    secondaryAction,
    footnote,
    headingId,
    typeSpeed = 90,
    holdFor = 2000,
    className,
}: TypewriterHeroProps) {
    const reducedMotion = usePrefersReducedMotion();
    const typed = useTypewriter(words, typeSpeed, holdFor, !reducedMotion);
    const longest = words.reduce((a, b) => (b.length > a.length ? b : a), '');

    return (
        <div className={cn('rounded-card bg-card/75 border px-6 py-10 text-center backdrop-blur-md sm:px-10 sm:py-14 lg:py-16', className)}>
            {announcement && (
                <div className="mb-6">
                    <Link
                        href={announcement.href}
                        className="bg-muted/70 hover:bg-muted focus-visible:ring-ring inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs transition-colors outline-none focus-visible:ring-2"
                    >
                        <span aria-hidden className="bg-primary size-1.5 rounded-full" />
                        <span className="font-medium">{announcement.label}</span>
                        {announcement.detail && <span className="text-muted-foreground">{announcement.detail}</span>}
                        <ArrowUpRight aria-hidden className="text-muted-foreground size-3" />
                    </Link>
                </div>
            )}

            <h1 id={headingId} className="font-display text-4xl text-balance sm:text-5xl lg:text-6xl">
                <span className="sr-only">{title ?? `${prefix} ${words[0] ?? ''}`}</span>
                <span aria-hidden>
                    {prefix}{' '}
                    {/* The longest word reserves the space, so the line doesn't jump as words change. On phones the word gets its own centred line. */}
                    <span className="relative grid justify-items-center whitespace-nowrap sm:inline-grid sm:justify-items-start sm:text-left">
                        <span className="invisible col-start-1 row-start-1">{longest}</span>
                        <span className="text-primary col-start-1 row-start-1">
                            {typed}
                            <span className="animate-caret ml-0.5 inline-block h-[0.85em] w-[3px] translate-y-[0.08em] rounded-sm bg-current motion-reduce:animate-none" />
                        </span>
                    </span>
                </span>
            </h1>

            {description && <p className="text-muted-foreground mx-auto mt-5 max-w-lg text-lg leading-relaxed text-pretty">{description}</p>}

            {(primaryAction || secondaryAction) && (
                <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    {primaryAction && (
                        <Button asChild size="lg" className="group h-12 min-w-44 text-base">
                            <Link href={primaryAction.href}>
                                {primaryAction.label}
                                <ArrowRight
                                    aria-hidden
                                    className="-ml-1 size-5! -translate-x-1 opacity-0 transition-[translate,opacity,margin] duration-200 group-hover:ml-0 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:ml-0 group-focus-visible:translate-x-0 group-focus-visible:opacity-100 motion-reduce:transition-none"
                                />
                            </Link>
                        </Button>
                    )}
                    {secondaryAction && (
                        <Button asChild size="lg" variant="ghost" className="h-12 min-w-40 text-base">
                            <Link href={secondaryAction.href}>{secondaryAction.label}</Link>
                        </Button>
                    )}
                </div>
            )}

            {footnote && <div className="text-muted-foreground mt-8 text-sm">{footnote}</div>}
        </div>
    );
}

export default TypewriterHero;
