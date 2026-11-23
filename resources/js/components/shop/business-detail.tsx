import { type ReactNode } from 'react';

/**
 * A configured business detail, or a visible "to be added" marker so a missing
 * legal detail is obvious rather than silently blank. Set it in .env (see config/shop.php).
 */
export function BusinessDetail({ value, label }: { value: string | null | undefined; label: string }): ReactNode {
    if (value) return value;

    return <mark className="bg-brand/10 text-brand rounded px-1 not-italic">[{label}: to be added]</mark>;
}
