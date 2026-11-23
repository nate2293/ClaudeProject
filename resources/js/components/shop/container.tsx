import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'react';

/** Centred, max-width wrapper with consistent horizontal gutters. */
export function Container({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8', className)} {...props} />;
}
