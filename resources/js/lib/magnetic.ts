/**
 * Magnetic buttons: elements drift towards a nearby cursor on a spring and
 * settle back when it leaves.
 *
 * One engine drives every magnet: a single passive pointer listener and a single
 * animation loop that only runs while something is moving. Each frame reads all
 * positions first, then writes, so it never forces extra layout. Off for touch
 * screens (no cursor) and when the visitor prefers reduced motion.
 */

export interface MagnetOptions {
    /** How far the element follows the cursor, 0 (not at all) to 1 (all the way). */
    strength: number;
    /** Distance in px from the element's edge within which it starts to pull. */
    radius: number;
}

interface Magnet extends MagnetOptions {
    el: HTMLElement;
    x: number;
    y: number;
    vx: number;
    vy: number;
}

/** Spring tuning: a little overshoot, settles in about half a second. */
const STIFFNESS = 0.16;
const DAMPING = 0.26;
const REST = 0.05;

const magnets = new Set<Magnet>();
let pointer: { x: number; y: number } | null = null;
let frame = 0;
let listening = false;

const enabled = () =>
    typeof window !== 'undefined' &&
    window.matchMedia('(hover: hover) and (pointer: fine)').matches &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function isDisabled(el: HTMLElement) {
    return el.matches(':disabled, [aria-disabled="true"]');
}

function schedule() {
    if (!frame) frame = requestAnimationFrame(tick);
}

function tick() {
    frame = 0;

    // Read: where each magnet should go, from its resting (untranslated) position.
    const targets = [...magnets].map((magnet) => {
        if (!pointer || !magnet.el.isConnected || isDisabled(magnet.el)) return { magnet, tx: 0, ty: 0 };

        const rect = magnet.el.getBoundingClientRect();
        const left = rect.left - magnet.x;
        const top = rect.top - magnet.y;
        const outsideX = Math.max(left - pointer.x, 0, pointer.x - (left + rect.width));
        const outsideY = Math.max(top - pointer.y, 0, pointer.y - (top + rect.height));
        const distance = Math.hypot(outsideX, outsideY);

        if (distance > magnet.radius) return { magnet, tx: 0, ty: 0 };

        // Full pull over the button, fading to nothing at the edge of the radius.
        const falloff = 1 - distance / magnet.radius;
        const dx = pointer.x - (left + rect.width / 2);
        const dy = pointer.y - (top + rect.height / 2);

        return { magnet, tx: dx * magnet.strength * falloff, ty: dy * magnet.strength * falloff };
    });

    // Write: step each spring and move the element (and, via CSS variables, its label).
    let moving = false;

    for (const { magnet, tx, ty } of targets) {
        magnet.vx = magnet.vx * (1 - DAMPING) + (tx - magnet.x) * STIFFNESS;
        magnet.vy = magnet.vy * (1 - DAMPING) + (ty - magnet.y) * STIFFNESS;
        magnet.x += magnet.vx;
        magnet.y += magnet.vy;

        const atRest = Math.abs(tx - magnet.x) < REST && Math.abs(ty - magnet.y) < REST && Math.abs(magnet.vx) < REST && Math.abs(magnet.vy) < REST;

        if (atRest) {
            magnet.x = tx;
            magnet.y = ty;
            magnet.vx = magnet.vy = 0;
        } else {
            moving = true;
        }

        paint(magnet);
    }

    if (moving) schedule();
}

function paint(magnet: Magnet) {
    const { el, x, y } = magnet;

    if (x === 0 && y === 0) {
        el.style.removeProperty('translate');
        el.style.removeProperty('--magnet-x');
        el.style.removeProperty('--magnet-y');
        return;
    }

    el.style.translate = `${x.toFixed(2)}px ${y.toFixed(2)}px`;
    el.style.setProperty('--magnet-x', `${x.toFixed(2)}px`);
    el.style.setProperty('--magnet-y', `${y.toFixed(2)}px`);
}

function onPointerMove(event: PointerEvent) {
    if (event.pointerType !== 'mouse' && event.pointerType !== 'pen') return;
    pointer = { x: event.clientX, y: event.clientY };
    schedule();
}

function onPointerLeave() {
    pointer = null;
    schedule();
}

function listen() {
    if (listening) return;
    listening = true;
    window.addEventListener('pointermove', onPointerMove, { passive: true });
    document.documentElement.addEventListener('pointerleave', onPointerLeave);
    window.addEventListener('blur', onPointerLeave);
    // Scrolling moves buttons under a still cursor.
    window.addEventListener('scroll', schedule, { passive: true, capture: true });
}

function unlisten() {
    if (!listening || magnets.size > 0) return;
    listening = false;
    window.removeEventListener('pointermove', onPointerMove);
    document.documentElement.removeEventListener('pointerleave', onPointerLeave);
    window.removeEventListener('blur', onPointerLeave);
    window.removeEventListener('scroll', schedule, { capture: true });
}

/** Make an element magnetic. Returns a function that undoes it. */
export function attachMagnet(el: HTMLElement, options: MagnetOptions): () => void {
    if (!enabled()) return () => {};

    const magnet: Magnet = { el, ...options, x: 0, y: 0, vx: 0, vy: 0 };
    magnets.add(magnet);
    listen();

    return () => {
        magnets.delete(magnet);
        magnet.x = magnet.y = 0;
        paint(magnet);
        unlisten();
    };
}
