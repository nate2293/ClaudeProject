import { Button, type ButtonProps } from '@/components/ui/button';
import { type ReactNode } from 'react';

export interface MagneticButtonProps extends Omit<ButtonProps, 'variant' | 'size' | 'magnetic' | 'magneticStrength' | 'magneticRadius'> {
    /** Button label, or icon + label. */
    children?: ReactNode;
    variant?: 'primary' | 'outline' | 'ghost' | 'dark';
    size?: 'sm' | 'md' | 'lg';
    /** Magnetic pull strength, 0 to 1. */
    strength?: number;
    /** Pixel radius in which the magnet activates. */
    radius?: number;
}

const variants = { primary: 'default', outline: 'outline', ghost: 'ghost', dark: 'dark' } as const;
const sizes = { sm: 'sm', md: 'default', lg: 'lg' } as const;

/**
 * A button that drifts towards the cursor on a spring, with its label moving a
 * little further for a parallax effect. Behaves as a normal button for keyboard,
 * screen readers and touch; the motion is off when reduced motion is preferred.
 * Every shared <Button> in the shop is magnetic by default; use this when you
 * want a stronger pull or the API below.
 *
 *   <MagneticButton variant="primary" size="lg">Get started</MagneticButton>
 */
export function MagneticButton({ children = 'Get started', variant = 'primary', size = 'md', strength = 0.4, radius = 80, type = 'button', ...props }: MagneticButtonProps) {
    return (
        <Button {...props} type={props.asChild ? undefined : type} variant={variants[variant]} size={sizes[size]} magnetic magneticStrength={strength} magneticRadius={radius}>
            {children}
        </Button>
    );
}

export default MagneticButton;
