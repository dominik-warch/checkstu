import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { PropsWithChildren, useEffect, useRef, useState } from 'react';

import { cn } from '@/lib/utils';

const PULL_THRESHOLD = 64;
const MAX_PULL = 96;

export default function PullToRefresh({ children }: PropsWithChildren) {
    const [pull, setPull] = useState(0);
    const [refreshing, setRefreshing] = useState(false);
    const startY = useRef<number | null>(null);
    const pullValue = useRef(0);

    useEffect(() => {
        const onTouchStart = (e: TouchEvent) => {
            if (window.scrollY <= 0 && !refreshing) {
                startY.current = e.touches[0].clientY;
            }
        };

        const onTouchMove = (e: TouchEvent) => {
            if (startY.current === null) return;

            const delta = e.touches[0].clientY - startY.current;
            if (delta <= 0 || window.scrollY > 0) {
                startY.current = null;
                pullValue.current = 0;
                setPull(0);
                return;
            }

            e.preventDefault();
            const next = Math.min(delta * 0.5, MAX_PULL);
            pullValue.current = next;
            setPull(next);
        };

        const onTouchEnd = () => {
            if (startY.current === null) return;
            startY.current = null;

            const shouldRefresh = pullValue.current >= PULL_THRESHOLD;
            pullValue.current = 0;
            setPull(0);

            if (shouldRefresh) {
                setRefreshing(true);
                router.reload({ onFinish: () => setRefreshing(false) });
            }
        };

        window.addEventListener('touchstart', onTouchStart, { passive: true });
        window.addEventListener('touchmove', onTouchMove, { passive: false });
        window.addEventListener('touchend', onTouchEnd);

        return () => {
            window.removeEventListener('touchstart', onTouchStart);
            window.removeEventListener('touchmove', onTouchMove);
            window.removeEventListener('touchend', onTouchEnd);
        };
    }, [refreshing]);

    const isDragging = startY.current !== null;
    const height = refreshing ? PULL_THRESHOLD : pull;
    const progress = Math.min(pull / PULL_THRESHOLD, 1);

    return (
        <>
            <div
                className={cn('flex items-center justify-center overflow-hidden', !isDragging && 'transition-[height] duration-200 ease-out')}
                style={{ height }}
            >
                <RefreshCw
                    className={cn('text-muted-foreground size-5', refreshing && 'animate-spin')}
                    style={refreshing ? undefined : { transform: `rotate(${progress * 360}deg)`, opacity: progress }}
                />
            </div>
            {children}
        </>
    );
}
