import { formatPrice } from '@/lib/format';
import { cn } from '@/lib/utils';

interface PriceProps {
    amount: number;
    compareAt?: number | null;
    className?: string;
}

export function Price({ amount, compareAt, className }: PriceProps) {
    const onSale = compareAt != null && compareAt > amount;

    return (
        <p className={cn('flex items-baseline gap-2 text-sm', className)}>
            <span className={cn('font-medium', onSale && 'text-brand')}>{formatPrice(amount)}</span>
            {onSale && (
                <span className="text-muted-foreground line-through">
                    <span className="sr-only">Was </span>
                    {formatPrice(compareAt)}
                </span>
            )}
        </p>
    );
}
