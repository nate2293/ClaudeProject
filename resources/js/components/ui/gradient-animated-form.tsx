import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ChevronLeft, Eye, EyeOff } from 'lucide-react';
import { type InputHTMLAttributes, type ReactNode, useEffect, useId, useRef, useState } from 'react';

/* ------------------------------------------------------------------------------------------------
 * Background: five slow-moving blobs of cloth-coloured light, plus one that follows the cursor.
 * ---------------------------------------------------------------------------------------------- */

const blobs = [
    { colour: '--gf-c1', className: 'motion-safe:animate-[gf-vertical_30s_ease_infinite] [transform-origin:center]' },
    { colour: '--gf-c2', className: 'motion-safe:animate-[gf-circle_20s_linear_infinite_reverse] [transform-origin:calc(50%-400px)]' },
    {
        colour: '--gf-c3',
        className: 'motion-safe:animate-[gf-circle_40s_linear_infinite] [transform-origin:calc(50%+400px)] !top-[calc(50%-var(--gf-size)/2+200px)] !left-[calc(50%-var(--gf-size)/2-500px)]',
    },
    { colour: '--gf-c4', className: 'opacity-70 motion-safe:animate-[gf-horizontal_40s_ease_infinite] [transform-origin:calc(50%-200px)]' },
    {
        colour: '--gf-c5',
        className:
            'motion-safe:animate-[gf-circle_20s_ease_infinite] [transform-origin:calc(50%-800px)_calc(50%+200px)] !h-[calc(var(--gf-size)*2)] !w-[calc(var(--gf-size)*2)] !top-[calc(50%-var(--gf-size))] !left-[calc(50%-var(--gf-size))]',
    },
];

const glow = (colour: string) => `radial-gradient(circle at center, rgba(var(${colour}), var(--gf-alpha)) 0, rgba(var(${colour}), 0) 50%) no-repeat`;

/** The animated backdrop. The cursor-following light only animates while catching up with the pointer. */
function GradientBackground() {
    const follower = useRef<HTMLDivElement>(null);
    const gooId = useId().replace(/:/g, '');

    useEffect(() => {
        const el = follower.current;
        const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!el || !finePointer || reducedMotion) return;

        let current = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
        let target = { ...current };
        let frame = 0;

        const step = () => {
            current = { x: current.x + (target.x - current.x) / 15, y: current.y + (target.y - current.y) / 15 };
            el.style.transform = `translate(${Math.round(current.x - window.innerWidth / 2)}px, ${Math.round(current.y - window.innerHeight / 2)}px)`;
            frame = Math.abs(target.x - current.x) + Math.abs(target.y - current.y) > 0.5 ? requestAnimationFrame(step) : 0;
        };
        const onMove = (event: PointerEvent) => {
            target = { x: event.clientX, y: event.clientY };
            if (!frame) frame = requestAnimationFrame(step);
        };

        window.addEventListener('pointermove', onMove, { passive: true });
        return () => {
            window.removeEventListener('pointermove', onMove);
            cancelAnimationFrame(frame);
        };
    }, []);

    return (
        <div aria-hidden className="pointer-events-none fixed inset-0 overflow-hidden" style={{ background: 'linear-gradient(40deg, var(--gf-bg1), var(--gf-bg2))' }}>
            {/* The "gooey" merge is costly, so phones get a plain blur. */}
            <div className={cn('h-full w-full [filter:blur(40px)]', 'md:[filter:var(--gf-goo)]')} style={{ ['--gf-goo' as string]: `url(#${gooId}) blur(10px)` }}>
                {blobs.map((blob) => (
                    <div key={blob.colour} className={cn('gf-blob', blob.className)} style={{ background: glow(blob.colour) }} />
                ))}
                <div ref={follower} className="gf-blob !top-0 !left-0 opacity-75" style={{ background: glow('--gf-interactive') }} />
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" className="hidden">
                <defs>
                    <filter id={gooId}>
                        <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur" />
                        <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -8" result="goo" />
                        <feBlend in="SourceGraphic" in2="goo" />
                    </filter>
                </defs>
            </svg>
        </div>
    );
}

/* ------------------------------------------------------------------------------------------------
 * Fields: underlined inputs whose label floats up on focus or once filled.
 * ---------------------------------------------------------------------------------------------- */

export interface GradientFieldProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'placeholder'> {
    id: string;
    label: string;
    error?: string;
    /** Adds a show/hide button to password fields. */
    revealable?: boolean;
}

export function GradientField({ id, label, error, revealable, type = 'text', className, ...props }: GradientFieldProps) {
    const [revealed, setRevealed] = useState(false);
    const errorId = `${id}-error`;
    const inputType = revealable && revealed ? 'text' : type;

    return (
        <div className={className}>
            <div className="relative border-b-2 border-current/30 transition-colors focus-within:border-current">
                <input
                    id={id}
                    type={inputType}
                    placeholder=" "
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? errorId : undefined}
                    className={cn(
                        'peer h-12 w-full bg-transparent pt-4 pr-9 pl-1 text-sm outline-none placeholder:text-transparent',
                        // Keep browser autofill from painting a yellow box on the glass.
                        '[&:-webkit-autofill]:[-webkit-text-fill-color:currentColor] [&:-webkit-autofill]:[transition:background-color_9999s_ease]',
                    )}
                    {...props}
                />
                <label
                    htmlFor={id}
                    className={cn(
                        'pointer-events-none absolute top-1 left-1 text-xs font-medium opacity-90 transition-all duration-300',
                        // Sits in the field while it's empty and unfocused; floats up otherwise.
                        'peer-[:placeholder-shown:not(:focus)]:top-1/2 peer-[:placeholder-shown:not(:focus)]:-translate-y-1/2 peer-[:placeholder-shown:not(:focus)]:text-sm peer-[:placeholder-shown:not(:focus)]:opacity-75',
                    )}
                >
                    {label}
                </label>
                {revealable && (
                    <button
                        type="button"
                        onClick={() => setRevealed((shown) => !shown)}
                        aria-label={revealed ? 'Hide password' : 'Show password'}
                        aria-pressed={revealed}
                        aria-controls={id}
                        className="focus-visible:ring-ring absolute top-1/2 right-1 -translate-y-1/3 rounded-full p-1 opacity-70 hover:opacity-100 focus-visible:ring-2 focus-visible:outline-none"
                    >
                        {revealed ? <EyeOff className="size-4" aria-hidden /> : <Eye className="size-4" aria-hidden />}
                    </button>
                )}
            </div>
            {error && (
                <p id={errorId} role="alert" className="mt-1.5 text-xs font-medium text-[#9c1028] dark:text-[#f0707f]">
                    {error}
                </p>
            )}
        </div>
    );
}

/** A "remember me"-style checkbox for the glass card. */
export function GradientCheckbox({ id, label, ...props }: InputHTMLAttributes<HTMLInputElement> & { id: string; label: string }) {
    return (
        <label htmlFor={id} className="flex cursor-pointer items-center gap-2 text-sm">
            <input id={id} type="checkbox" className="size-4 accent-black dark:accent-white" {...props} />
            {label}
        </label>
    );
}

/** The primary button's look on the glass card (pass to <Button className=…>). */
export const gradientButtonClass =
    'h-12 w-full text-base font-semibold bg-black text-white hover:bg-[#5c4e4e] dark:border dark:border-white/50 dark:bg-white/10 dark:backdrop-blur-md dark:hover:bg-white dark:hover:text-black dark:hover:shadow-[0_0_18px_rgb(255_255_255/0.5)]';

/* ------------------------------------------------------------------------------------------------
 * The page shell: backdrop, a way back to the shop, and the frosted card.
 * ---------------------------------------------------------------------------------------------- */

export interface GradientAnimatedFormProps {
    title?: string;
    description?: string;
    /** The form. Without it a sample sign-up form is shown. */
    children?: ReactNode;
    /** Where the logo and "Back to the shop" lead. */
    homeHref?: string;
    brand?: ReactNode;
    className?: string;
}

/**
 * A frosted sign-in card over slowly drifting light in the shop's indigo and
 * denim, with a glow that follows the cursor. Light and dark themes, reduced
 * motion respected, cursor effects off on touch screens.
 */
export function GradientAnimatedForm({ title = 'Create an account', description, children, homeHref = '/', brand = 'Loom & Lane', className }: GradientAnimatedFormProps) {
    return (
        <div className="gradient-form relative isolate flex min-h-svh flex-col text-black dark:text-[#d1d0d0]">
            <GradientBackground />

            <header className="relative z-10 flex items-center justify-between px-5 py-5 sm:px-8">
                <Link href={homeHref} className="font-logo text-xl">
                    {brand}
                </Link>
                <Link href={homeHref} className="inline-flex items-center gap-1 text-sm opacity-80 hover:opacity-100">
                    <ChevronLeft className="size-4" aria-hidden />
                    Back to the shop
                </Link>
            </header>

            <main className="relative z-10 flex flex-1 items-center justify-center px-4 pb-12">
                <section
                    aria-labelledby="gradient-form-title"
                    className={cn(
                        'rounded-card relative w-full max-w-[26rem] overflow-hidden border px-6 py-9 shadow-[0_20px_50px_rgb(0_0_0/0.18)] backdrop-blur-xl sm:px-9',
                        'border-white/70 bg-white/55 dark:border-white/10 dark:bg-white/5',
                        // The fine light along the top edge.
                        "before:absolute before:inset-x-0 before:top-0 before:h-0.5 before:bg-[linear-gradient(90deg,transparent,white_34%,white_89%,transparent)] before:opacity-40 before:blur-[0.5px] before:content-['']",
                        className,
                    )}
                >
                    <h1 id="gradient-form-title" className="font-display text-center text-2xl">
                        {title}
                    </h1>
                    {description && <p className="mt-2 text-center text-sm opacity-75">{description}</p>}
                    <div className="mt-7">{children ?? <SampleForm />}</div>
                </section>
            </main>
        </div>
    );
}

/** Renders when no form is passed, so the component works out of the box. */
function SampleForm() {
    return (
        <form onSubmit={(event) => event.preventDefault()} className="space-y-5">
            <GradientField id="sample-name" label="Name" autoComplete="name" required />
            <GradientField id="sample-email" label="Email" type="email" autoComplete="email" required />
            <GradientField id="sample-password" label="Password" type="password" revealable autoComplete="new-password" required />
            <GradientCheckbox id="sample-remember" label="Remember me" />
            <button type="submit" className={cn('rounded-full', gradientButtonClass)}>
                Sign up
            </button>
        </form>
    );
}

export default GradientAnimatedForm;
