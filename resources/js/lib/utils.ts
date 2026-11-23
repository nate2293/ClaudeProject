import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Whether `href` (absolute or relative) points at the current Inertia page URL,
 * comparing path and query string.
 */
export function isCurrentUrl(href: string, pageUrl: string): boolean {
    const target = new URL(href, 'http://localhost');
    const current = new URL(pageUrl, 'http://localhost');

    return target.pathname === current.pathname && target.search === current.search;
}
