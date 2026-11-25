import { GradientAnimatedForm } from '@/components/ui/gradient-animated-form';
import { type ReactNode } from 'react';

/** Every sign-in page (log in, register, password reset, verification) shares the gradient card. */
export default function AuthLayout({ children, title, description }: { children: ReactNode; title: string; description?: string }) {
    return (
        <GradientAnimatedForm title={title} description={description} homeHref={route('home')}>
            {children}
        </GradientAnimatedForm>
    );
}
