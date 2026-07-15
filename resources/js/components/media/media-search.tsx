import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import MediaPoster from '@/components/media/media-poster';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { t } from '@/lib/i18n';
import type { MediaSearchResult, MediaType } from '@/types/media';

interface MediaSearchProps {
    onAdded?: () => void;
}

/**
 * Debounced remote search against our TMDb proxy (GET media.search), not a
 * client-side filter over pre-loaded data — the closest existing precedent
 * (TitleAutocomplete) filters a local array, so this adds the loading/empty/
 * error states that pattern doesn't need.
 */
export default function MediaSearch({ onAdded }: MediaSearchProps) {
    const [type, setType] = useState<MediaType>('movie');
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<MediaSearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(false);
    const [addingKey, setAddingKey] = useState<string | null>(null);

    useEffect(() => {
        const trimmed = query.trim();
        if (trimmed === '') {
            setResults([]);
            setError(false);
            setLoading(false);
            return;
        }

        setLoading(true);
        const timeout = setTimeout(() => {
            fetch(route('media.search', { query: trimmed, type }), { headers: { Accept: 'application/json' } })
                .then((res) => {
                    if (!res.ok) throw new Error('search failed');
                    return res.json();
                })
                .then((data: { results: MediaSearchResult[] }) => {
                    setResults(data.results);
                    setError(false);
                })
                .catch(() => setError(true))
                .finally(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timeout);
    }, [query, type]);

    function add(result: MediaSearchResult, status: 'watchlist' | 'completed') {
        const key = `${result.type}-${result.tmdb_id}`;
        setAddingKey(key);
        router.post(
            route('media.entries.store'),
            { tmdb_id: result.tmdb_id, type: result.type, status },
            {
                preserveScroll: true,
                onFinish: () => setAddingKey(null),
                onSuccess: () => onAdded?.(),
            },
        );
    }

    return (
        <div className="flex flex-col gap-4">
            <ToggleGroup type="single" value={type} onValueChange={(v) => v && setType(v as MediaType)}>
                <ToggleGroupItem value="movie">{t('media.typeMovie')}</ToggleGroupItem>
                <ToggleGroupItem value="tv">{t('media.typeTv')}</ToggleGroupItem>
            </ToggleGroup>

            <Input
                autoFocus
                autoComplete="off"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder={type === 'movie' ? t('media.searchPlaceholderMovie') : t('media.searchPlaceholderTv')}
            />

            {loading && <p className="text-muted-foreground text-sm">{t('common.loading')}</p>}
            {!loading && error && <p className="text-sm text-rose-600 dark:text-rose-400">{t('media.searchError')}</p>}
            {!loading && !error && query.trim() !== '' && results.length === 0 && (
                <p className="text-muted-foreground text-sm">{t('media.searchEmpty')}</p>
            )}

            <div className="flex flex-col gap-2">
                {results.map((result) => {
                    const key = `${result.type}-${result.tmdb_id}`;
                    const busy = addingKey === key;

                    return (
                        <div key={key} className="flex items-center gap-3 rounded-lg border p-2">
                            <MediaPoster path={result.poster_path} alt={result.title} className="h-16 w-11" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">{result.title}</p>
                                {result.release_date && <p className="text-muted-foreground text-xs">{result.release_date.slice(0, 4)}</p>}
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <Button size="sm" variant="outline" disabled={busy} onClick={() => add(result, 'watchlist')}>
                                    {t('media.addToWatchlist')}
                                </Button>
                                {result.type === 'movie' && (
                                    <Button size="sm" disabled={busy} onClick={() => add(result, 'completed')}>
                                        {t('media.markWatched')}
                                    </Button>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
