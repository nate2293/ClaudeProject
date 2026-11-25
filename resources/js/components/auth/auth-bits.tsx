import { GoogleButton } from '@/components/shop/google-button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { type ComponentProps, type ReactNode } from 'react';

/** "Continue with Google", then a divider before the email form. */
export function GoogleOption() {
    return (
        <div className="mb-6">
            <GoogleButton />
            <div className="mt-6 flex items-center gap-3 text-xs opacity-70" aria-hidden>
                <span className="h-px flex-1 bg-current opacity-30" />
                or use your email
                <span className="h-px flex-1 bg-current opacity-30" />
            </div>
        </div>
    );
}

/** A link on the glass card: bold, underlined on hover. */
export function AuthLink({ className, ...props }: ComponentProps<typeof Link>) {
    return <Link {...props} className={cn('font-semibold underline-offset-4 hover:underline', className)} />;
}

/** A confirmation message at the top of the card (e.g. "reset link sent"). */
export function AuthStatus({ children }: { children: ReactNode }) {
    return (
        <p
            role="status"
            className="rounded-tile mb-5 bg-emerald-600/10 px-4 py-3 text-center text-sm font-medium text-emerald-800 dark:text-emerald-200"
        >
            {children}
        </p>
    );
}
