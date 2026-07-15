import { usePage } from '@inertiajs/react';

import { posterUrl } from '@/lib/tmdb';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';

interface MediaPosterProps {
    path: string | null;
    alt: string;
    size?: 'w92' | 'w185' | 'w342';
    className?: string;
}

export default function MediaPoster({ path, alt, size = 'w92', className }: MediaPosterProps) {
    const { tmdbImageBaseUrl } = usePage<SharedData>().props;
    const src = posterUrl(tmdbImageBaseUrl, path, size);

    if (!src) {
        return <div className={cn('bg-muted shrink-0 rounded', className)} aria-hidden />;
    }

    return <img src={src} alt={alt} className={cn('shrink-0 rounded object-cover', className)} />;
}
