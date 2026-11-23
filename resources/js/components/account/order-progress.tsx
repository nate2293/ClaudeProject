import { cn } from '@/lib/utils';
import type { AccountOrder } from '@/types/shop';

const STEP_LABELS = ['Confirmed', 'Packed', 'On its way'];

/** Where an order is: three segments that fill as it's confirmed, packed and sent. */
export function OrderProgress({ progress }: { progress: AccountOrder['progress'] }) {
    if (progress.tone === 'muted') return null;

    return (
        <div className="mt-4">
            <div className="grid grid-cols-3 gap-1" aria-hidden>
                {STEP_LABELS.map((label, i) => (
                    <span
                        key={label}
                        className={cn(
                            'h-1.5',
                            i < progress.step ? (progress.tone === 'done' ? 'bg-primary' : 'bg-primary/80') : 'bg-secondary',
                            progress.tone === 'waiting' && i === 0 && 'bg-primary/30 motion-safe:animate-pulse',
                        )}
                    />
                ))}
            </div>
            <ol className="text-muted-foreground mt-2 grid grid-cols-3 text-xs">
                {STEP_LABELS.map((label, i) => (
                    <li
                        key={label}
                        className={cn(i === 1 && 'text-center', i === 2 && 'text-right', i < progress.step && 'text-foreground font-medium')}
                    >
                        {label}
                        {i < progress.step && <span className="sr-only"> (done)</span>}
                    </li>
                ))}
            </ol>
        </div>
    );
}
