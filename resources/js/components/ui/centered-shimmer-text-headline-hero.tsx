import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight } from 'lucide-react';
import { type ReactNode } from 'react';

export interface HeroLink {
    label: string;
    href: string;
}

export interface HeroStat {
    value: string;
    label: string;
}

export interface CenteredShimmerTextHeadlineHeroProps {
    /** The pill above the headline. */
    announcement?: { label: string; text: string; href: string };
    /** First line of the headline. */
    headline?: ReactNode;
    /** Second line, drawn with the shimmer. */
    shimmerText?: string;
    description?: ReactNode;
    primaryCta?: HeroLink;
    secondaryCta?: HeroLink;
    /** Small print under the buttons. */
    note?: ReactNode;
    /** Up to four figures in the bar along the bottom. */
    stats?: HeroStat[];
    className?: string;
}

const defaults: Required<Omit<CenteredShimmerTextHeadlineHeroProps, 'className'>> = {
    announcement: { label: 'New in', text: 'The winter collection is here', href: '/shop' },
    headline: 'Clothes in natural cloth,',
    shimmerText: 'made to be worn for years',
    description: 'Merino, linen, waxed cotton and selvedge denim, designed in London and made in small runs.',
    primaryCta: { label: 'Shop the collection', href: '/shop' },
    secondaryCta: { label: 'Our story', href: '/about' },
    note: 'Free UK delivery over £75 · Free returns within 30 days',
    stats: [
        { value: '100%', label: 'Natural fibres' },
        { value: '30 days', label: 'Free returns' },
        { value: '£75', label: 'Free delivery over' },
        { value: '2–4 days', label: 'UK delivery' },
    ],
};

/**
 * A centred hero card: announcement pill, a two-line headline whose second line
 * shimmers, two calls to action, small print, and a bar of key figures.
 * Frosted so a background (e.g. GradientBackground) shows through. The shimmer
 * is still for reduced-motion users; text stays readable without it.
 */
export function CenteredShimmerTextHeadlineHero(props: CenteredShimmerTextHeadlineHeroProps) {
    const { announcement, headline, shimmerText, description, primaryCta, secondaryCta, note, stats, className } = { ...defaults, ...props };

    return (
        <section aria-labelledby="hero-heading" className={cn('mx-auto w-full max-w-6xl', className)}>
            <div className="rounded-card bg-card/70 overflow-hidden border border-white/60 shadow-[0_20px_60px_-24px_rgb(31_43_71/0.35)] backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.04]">
                <div className="px-6 py-10 text-center sm:px-10 sm:py-14 lg:px-14 lg:py-16">
                    {announcement && (
                        <div className="mb-7 flex justify-center">
                            <Link
                                href={announcement.href}
                                className="bg-background/60 hover:bg-background focus-visible:ring-ring inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs transition-colors focus-visible:ring-2 focus-visible:outline-none"
                            >
                                <span aria-hidden className="bg-brand size-1.5 rounded-full" />
                                <span className="font-semibold">{announcement.label}</span>
                                <span aria-hidden className="text-muted-foreground">
                                    ·
                                </span>
                                <span className="text-muted-foreground">{announcement.text}</span>
                                <ArrowUpRight aria-hidden className="text-muted-foreground size-3" />
                            </Link>
                        </div>
                    )}

                    <h1 id="hero-heading" className="font-display mx-auto max-w-4xl text-[2.25rem] leading-[1.05] text-balance sm:text-5xl lg:text-[4rem]">
                        <span>{headline}</span>
                        {shimmerText && (
                            <>
                                <br />
                                <span
                                    className="inline-block bg-clip-text text-transparent motion-safe:animate-[hero-shimmer_3.5s_ease-in-out_infinite]"
                                    style={{
                                        backgroundImage:
                                            'linear-gradient(110deg, var(--foreground) 35%, color-mix(in oklab, var(--foreground) 40%, transparent) 50%, var(--foreground) 65%)',
                                        backgroundSize: '200% 100%',
                                    }}
                                >
                                    {shimmerText}
                                </span>
                            </>
                        )}
                    </h1>

                    {description && <p className="text-muted-foreground mx-auto mt-5 max-w-xl text-base leading-relaxed sm:text-lg">{description}</p>}

                    {(primaryCta || secondaryCta) && (
                        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            {primaryCta && (
                                <Button size="lg" asChild className="group h-12 min-w-52 text-base">
                                    <Link href={primaryCta.href}>
                                        {primaryCta.label}
                                        <ArrowRight
                                            aria-hidden
                                            className="-translate-x-1 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100"
                                        />
                                    </Link>
                                </Button>
                            )}
                            {secondaryCta && (
                                <Button size="lg" variant="ghost" asChild className="h-12 min-w-52 text-base">
                                    <Link href={secondaryCta.href}>{secondaryCta.label}</Link>
                                </Button>
                            )}
                        </div>
                    )}

                    {note && <p className="text-muted-foreground mt-5 text-sm">{note}</p>}
                </div>

                {stats.length > 0 && (
                    <dl className="bg-muted/40 border-t px-6 py-7 sm:px-10">
                        <div className="mx-auto grid max-w-3xl grid-cols-2 gap-6 md:grid-cols-4 md:gap-8">
                            {stats.slice(0, 4).map((stat) => (
                                <div key={stat.label} className="flex flex-col-reverse text-center">
                                    <dt className="text-muted-foreground mt-1 text-xs">{stat.label}</dt>
                                    <dd className="font-display text-2xl tabular-nums">{stat.value}</dd>
                                </div>
                            ))}
                        </div>
                    </dl>
                )}
            </div>
        </section>
    );
}

export default CenteredShimmerTextHeadlineHero;
