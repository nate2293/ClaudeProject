import { cn } from '@/lib/utils';
import { Minus, Plus } from 'lucide-react';

interface QuantityStepperProps {
    value: number;
    onChange: (value: number) => void;
    min?: number;
    max: number;
    disabled?: boolean;
    size?: 'sm' | 'default';
    label?: string;
}

export function QuantityStepper({ value, onChange, min = 1, max, disabled, size = 'default', label = 'Quantity' }: QuantityStepperProps) {
    const buttonClass = cn(
        'hover:bg-accent flex items-center justify-center rounded-full transition-colors disabled:pointer-events-none disabled:opacity-40',
        size === 'sm' ? 'size-8' : 'size-11',
    );

    return (
        <div className={cn('inline-flex items-center rounded-full border', disabled && 'opacity-60')} role="group" aria-label={label}>
            <button
                type="button"
                className={buttonClass}
                onClick={() => onChange(value - 1)}
                disabled={disabled || value <= min}
                aria-label="Decrease quantity"
            >
                <Minus className="size-4" />
            </button>
            <span className={cn('text-center text-sm font-medium tabular-nums', size === 'sm' ? 'w-8' : 'w-10')} aria-live="polite">
                {value}
            </span>
            <button
                type="button"
                className={buttonClass}
                onClick={() => onChange(value + 1)}
                disabled={disabled || value >= max}
                aria-label="Increase quantity"
            >
                <Plus className="size-4" />
            </button>
        </div>
    );
}
