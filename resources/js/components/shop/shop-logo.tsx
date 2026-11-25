import { storeName } from '@/data/storefront';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

export function ShopLogo({ className }: { className?: string }) {
    return (
        <Link href={route('home')} className={cn('font-logo text-xl sm:text-2xl', className)} aria-label={`${storeName} home`}>
            {storeName}
        </Link>
    );
}
