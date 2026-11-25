import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { magneticLabel, useMagnetic } from '@/hooks/use-magnetic';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-full text-sm font-medium ring-offset-background transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                default: 'bg-primary text-primary-foreground hover:bg-primary/90',
                destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
                secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                ghost: 'hover:bg-accent hover:text-accent-foreground',
                link: 'text-primary underline-offset-4 hover:underline',
                /** Ink-dark in both themes. */
                dark: 'bg-[#171b12] text-[#f6f5ee] hover:bg-[#4e5a2e] dark:bg-[#12160d] dark:hover:bg-black',
            },
            size: {
                default: 'h-10 px-4 py-2',
                sm: 'h-9 rounded-full px-3',
                lg: 'h-11 rounded-full px-8',
                icon: 'h-10 w-10',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
    asChild?: boolean;
    /**
     * Drift towards a nearby cursor (see lib/magnetic). On by default, except for
     * icon buttons and text links, where a moving target is harder to hit.
     */
    magnetic?: boolean;
    /** Magnetic pull, 0 to 1. */
    magneticStrength?: number;
    /** Distance in px from the button's edge at which the pull starts. */
    magneticRadius?: number;
}

function assignRef<T>(ref: React.Ref<T> | undefined, value: T | null) {
    if (typeof ref === 'function') ref(value);
    else if (ref) (ref as React.RefObject<T | null>).current = value;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    ({ className, variant, size, asChild = false, magnetic, magneticStrength = 0.3, magneticRadius = 60, children, ...props }, ref) => {
        const isMagnetic = magnetic ?? (size !== 'icon' && variant !== 'link');
        const magnetRef = useMagnetic<HTMLButtonElement>({ strength: magneticStrength, radius: magneticRadius, enabled: isMagnetic });
        const setRef = React.useCallback(
            (el: HTMLButtonElement | null) => {
                magnetRef(el);
                assignRef(ref, el);
            },
            [magnetRef, ref],
        );
        const classes = cn(buttonVariants({ variant, size, className }));

        if (asChild) {
            // Wrap the child's own content (e.g. a link's text) so it can move with the parallax.
            const child = React.Children.only(children) as React.ReactElement<{ children?: React.ReactNode }>;
            const content = isMagnetic && React.isValidElement(child) ? React.cloneElement(child, undefined, <span className={magneticLabel}>{child.props.children}</span>) : child;

            return (
                <Slot className={classes} ref={setRef} {...props}>
                    {content}
                </Slot>
            );
        }

        return (
            <button className={classes} ref={setRef} {...props}>
                {isMagnetic ? <span className={magneticLabel}>{children}</span> : children}
            </button>
        );
    },
);
Button.displayName = 'Button';

export { Button, buttonVariants };
