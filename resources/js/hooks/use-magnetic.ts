import { attachMagnet, type MagnetOptions } from '@/lib/magnetic';
import { useCallback, useRef } from 'react';

/**
 * A ref callback that makes the element it's attached to magnetic (see lib/magnetic).
 * Pass `false` to switch it off, e.g. for icon buttons.
 */
export function useMagnetic<T extends HTMLElement>({
    strength = 0.4,
    radius = 80,
    enabled = true,
}: Partial<MagnetOptions> & { enabled?: boolean } = {}) {
    const detach = useRef<() => void>(undefined);

    return useCallback(
        (el: T | null) => {
            detach.current?.();
            detach.current = el && enabled ? attachMagnet(el, { strength, radius }) : undefined;
        },
        [strength, radius, enabled],
    );
}

/** Classes for a button's label: it moves a little further than the button, for depth. */
export const magneticLabel =
    'pointer-events-none inline-flex items-center justify-center [gap:inherit] [translate:calc(var(--magnet-x,0px)*0.35)_calc(var(--magnet-y,0px)*0.35)]';
