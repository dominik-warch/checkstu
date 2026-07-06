import { PointerEvent as ReactPointerEvent, useRef, useState } from 'react';

const THRESHOLD = 96; // px past which a release triggers completion
const MAX = 140; // max drag distance (rubber-banded)

/**
 * Lightweight right-swipe-to-complete gesture using native pointer events.
 * No dependency; `touch-action: pan-y` lets vertical scrolling pass through.
 */
export function useSwipeToComplete(onComplete: () => void, enabled: boolean) {
    const [dx, setDx] = useState(0);
    const [animating, setAnimating] = useState(false);

    const dxRef = useRef(0);
    const start = useRef({ x: 0, y: 0 });
    const axis = useRef<null | 'h' | 'v'>(null);
    const active = useRef(false);
    const swiped = useRef(false);

    const set = (value: number) => {
        dxRef.current = value;
        setDx(value);
    };

    const onPointerDown = (e: ReactPointerEvent) => {
        if (!enabled) return;
        active.current = true;
        axis.current = null;
        swiped.current = false;
        start.current = { x: e.clientX, y: e.clientY };
        setAnimating(false);
    };

    const onPointerMove = (e: ReactPointerEvent) => {
        if (!active.current) return;
        const ddx = e.clientX - start.current.x;
        const ddy = e.clientY - start.current.y;

        if (axis.current === null) {
            if (Math.abs(ddx) < 6 && Math.abs(ddy) < 6) return;
            axis.current = Math.abs(ddx) > Math.abs(ddy) ? 'h' : 'v';
            if (axis.current === 'h') e.currentTarget.setPointerCapture?.(e.pointerId);
        }

        if (axis.current === 'h') {
            swiped.current = true;
            set(Math.max(0, Math.min(ddx, MAX)));
        }
    };

    const end = () => {
        if (!active.current) return;
        active.current = false;
        setAnimating(true);

        if (axis.current === 'h' && dxRef.current >= THRESHOLD) {
            set(MAX * 3); // slide off, then complete
            onComplete();
        } else {
            set(0);
        }
        axis.current = null;
    };

    return {
        dx,
        animating,
        swiped, // ref: true if the last interaction was a horizontal swipe (suppress click)
        handlers: {
            onPointerDown,
            onPointerMove,
            onPointerUp: end,
            onPointerCancel: end,
        },
    };
}
