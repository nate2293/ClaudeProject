import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { LoaderCircle } from 'lucide-react';
import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Every state the button can be in, in one place (hover and pressed darken the
 * fill with color-mix, so they stay within the palette in both themes):
 * - hover: a small darkening of the fill (150ms)
 * - active (pressed): darker still, and a 0.98 scale-down
 * - focus-visible: a 3px ring with an offset, keyboard only
 * - disabled / aria-disabled: faded, with a not-allowed cursor
 * - loading: a spinner over the label, busy and disabled
 * - selected: aria-pressed="true" fills outline, secondary and ghost buttons
 */
const buttonVariants = cva(
    [
        'relative inline-flex shrink-0 items-center justify-center gap-2 rounded-md text-sm font-medium whitespace-nowrap select-none',
        'transition-[background-color,border-color,color,box-shadow,scale] duration-150 ease-out',
        'focus-visible:ring-ring focus-visible:ring-offset-background focus-visible:ring-[3px] focus-visible:ring-offset-2 focus-visible:outline-none',
        'active:scale-[0.98] motion-reduce:transition-none motion-reduce:active:scale-100',
        'disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100',
        'aria-disabled:pointer-events-none aria-disabled:cursor-not-allowed aria-disabled:opacity-50',
        'aria-busy:cursor-wait',
        // A 44px tap target on touch screens, whatever the visual size.
        '[@media(pointer:coarse)]:min-h-11',
        '[&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
    ],
    {
        variants: {
            variant: {
                default: 'bg-primary text-primary-foreground shadow-xs hover:bg-[color-mix(in_oklab,var(--primary),black_10%)] active:bg-[color-mix(in_oklab,var(--primary),black_20%)]',
                destructive: 'bg-destructive text-destructive-foreground shadow-xs hover:bg-[color-mix(in_oklab,var(--destructive),black_10%)] active:bg-[color-mix(in_oklab,var(--destructive),black_20%)]',
                outline: 'border-input bg-background hover:bg-accent hover:text-accent-foreground active:bg-[color-mix(in_oklab,var(--accent),black_6%)] aria-pressed:border-primary aria-pressed:bg-primary aria-pressed:text-primary-foreground border shadow-xs',
                secondary: 'bg-secondary text-secondary-foreground hover:bg-[color-mix(in_oklab,var(--secondary),black_5%)] active:bg-[color-mix(in_oklab,var(--secondary),black_10%)] aria-pressed:bg-primary aria-pressed:text-primary-foreground',
                ghost: 'hover:bg-accent hover:text-accent-foreground active:bg-[color-mix(in_oklab,var(--accent),black_6%)] aria-pressed:bg-primary aria-pressed:text-primary-foreground',
                link: 'text-primary underline-offset-4 hover:underline active:scale-100',
                /** Rich brown, in both themes. */
                dark: 'bg-[#4d1717] text-[#f2f2f2] shadow-xs hover:bg-[#5e1d1d] active:bg-[#3f1212]',
                /** No colours: bring your own with className (the states above still apply). */
                custom: '',
            },
            size: {
                default: 'h-10 px-4 py-2',
                sm: 'h-9 px-3',
                lg: 'h-11 px-8',
                icon: 'size-10 [@media(pointer:coarse)]:min-w-11',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
    /** Render the child element (e.g. a link) with the button's styles instead of a <button>. */
    asChild?: boolean;
    /** Show a spinner over the label and block clicks until the action finishes. Buttons only, not asChild. */
    loading?: boolean;
    /** What screen readers hear while loading. */
    loadingLabel?: string;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    ({ className, variant, size, asChild = false, loading = false, loadingLabel = 'Loading', disabled, children, ...props }, ref) => {
        const classes = cn(buttonVariants({ variant, size, className }));

        if (asChild) {
            return (
                <Slot className={classes} ref={ref} {...props}>
                    {children}
                </Slot>
            );
        }

        return (
            <button className={classes} ref={ref} disabled={disabled || loading} aria-busy={loading || undefined} {...props}>
                {loading ? (
                    <>
                        {/* Keep the label's width so the button doesn't change size. */}
                        <span aria-hidden className="invisible inline-flex items-center gap-2">
                            {children}
                        </span>
                        <span className="absolute inset-0 flex items-center justify-center">
                            <LoaderCircle className="animate-spin" aria-hidden />
                            <span className="sr-only">{loadingLabel}</span>
                        </span>
                    </>
                ) : (
                    children
                )}
            </button>
        );
    },
);
Button.displayName = 'Button';

export { Button, buttonVariants };
