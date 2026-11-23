import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const DISMISS_AFTER_MS = 4000;

/**
 * Shows the latest `success` / `error` flash message from Laravel as a toast.
 * Each Inertia response produces a new `flash` object, so repeated identical
 * messages (e.g. adding the same item twice) still re-trigger the toast.
 */
export function FlashMessage() {
    const { flash } = usePage<SharedData>().props;
    const [visible, setVisible] = useState(false);

    const message = flash.error ?? flash.success;
    const isError = Boolean(flash.error);

    useEffect(() => {
        if (!message) return;

        setVisible(true);
        const timer = window.setTimeout(() => setVisible(false), isError ? DISMISS_AFTER_MS * 2 : DISMISS_AFTER_MS);

        return () => window.clearTimeout(timer);
    }, [flash, message, isError]);

    if (!message || !visible) return null;

    return (
        <div className="pointer-events-none fixed inset-x-0 top-20 z-50 flex justify-center px-4">
            <div
                role={isError ? 'alert' : 'status'}
                className={cn(
                    'animate-in fade-in slide-in-from-top-2 pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-lg border p-4 text-sm shadow-lg',
                    isError ? 'border-destructive/30 bg-background text-destructive' : 'bg-foreground text-background border-transparent',
                )}
            >
                {isError ? <AlertCircle className="mt-0.5 size-4 shrink-0" /> : <CheckCircle2 className="mt-0.5 size-4 shrink-0" />}
                <p className="flex-1">{message}</p>
                <button type="button" onClick={() => setVisible(false)} aria-label="Dismiss" className="opacity-70 hover:opacity-100">
                    <X className="size-4" />
                </button>
            </div>
        </div>
    );
}
