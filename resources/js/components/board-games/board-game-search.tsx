import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { t } from '@/lib/i18n';
import type { BoardGameSearchResult } from '@/types/board-games';

interface BoardGameSearchProps {
    onAdded?: () => void;
}

/**
 * Debounced remote search against BGG (GET boardgames.search), same pattern
 * as MediaSearch — not a client-side filter, results come from a remote API.
 * Falls back to a manual-entry form for games BGG doesn't have (house rules
 * variants, self-published games, etc.).
 */
export default function BoardGameSearch({ onAdded }: BoardGameSearchProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<BoardGameSearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<'failed' | 'unavailable' | null>(null);
    const [addingId, setAddingId] = useState<number | null>(null);
    const [manualName, setManualName] = useState('');
    const [addingManual, setAddingManual] = useState(false);

    useEffect(() => {
        const trimmed = query.trim();
        if (trimmed === '') {
            setResults([]);
            setError(null);
            setLoading(false);
            return;
        }

        setLoading(true);
        const timeout = setTimeout(() => {
            // A non-ok response is parsed too (not just discarded) — the backend flags BGG
            // being unreachable/blocking this network with `unavailable: true`, distinct from
            // a plain failure, so the UI can say "not your fault" instead of a generic error.
            fetch(route('boardgames.search', { query: trimmed }), { headers: { Accept: 'application/json' } })
                .then(async (res) => {
                    const data = (await res.json()) as { results: BoardGameSearchResult[]; unavailable?: boolean };
                    if (!res.ok) throw new Error(data.unavailable ? 'unavailable' : 'failed');
                    return data;
                })
                .then((data) => {
                    setResults(data.results);
                    setError(null);
                })
                .catch((err: Error) => setError(err.message === 'unavailable' ? 'unavailable' : 'failed'))
                .finally(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timeout);
    }, [query]);

    function add(result: BoardGameSearchResult, status: 'owned' | 'wishlist') {
        setAddingId(result.bgg_id);
        router.post(
            route('boardgames.store'),
            { bgg_id: result.bgg_id, status },
            { preserveScroll: true, onFinish: () => setAddingId(null), onSuccess: () => onAdded?.() },
        );
    }

    function addManual(status: 'owned' | 'wishlist') {
        if (manualName.trim() === '') return;
        setAddingManual(true);
        router.post(
            route('boardgames.store'),
            { name: manualName.trim(), status },
            {
                preserveScroll: true,
                onFinish: () => setAddingManual(false),
                onSuccess: () => {
                    setManualName('');
                    onAdded?.();
                },
            },
        );
    }

    const isEmpty = query.trim() !== '' && results.length === 0;

    return (
        <div className="flex flex-col gap-4">
            <Input autoFocus autoComplete="off" value={query} onChange={(e) => setQuery(e.target.value)} placeholder={t('boardGames.searchPlaceholder')} />

            {loading && <p className="text-muted-foreground text-sm">{t('common.loading')}</p>}
            {!loading && error === 'failed' && <p className="text-sm text-rose-600 dark:text-rose-400">{t('boardGames.searchError')}</p>}
            {!loading && error === 'unavailable' && <p className="text-sm text-rose-600 dark:text-rose-400">{t('boardGames.searchUnavailable')}</p>}
            {!loading && !error && isEmpty && <p className="text-muted-foreground text-sm">{t('boardGames.searchEmpty')}</p>}

            <div className="flex flex-col gap-2">
                {results.map((result) => {
                    const busy = addingId === result.bgg_id;

                    return (
                        <div key={result.bgg_id} className="flex items-center gap-3 rounded-lg border p-2">
                            <div className="min-w-0 flex-1">
                                <p className="line-clamp-2 font-medium break-words">{result.name}</p>
                                {result.year_published && <p className="text-muted-foreground text-xs">{result.year_published}</p>}
                            </div>
                            <div className="flex shrink-0 flex-col gap-1">
                                <Button size="sm" variant="outline" disabled={busy} onClick={() => add(result, 'wishlist')}>
                                    {t('boardGames.wishlist')}
                                </Button>
                                <Button size="sm" disabled={busy} onClick={() => add(result, 'owned')}>
                                    {t('boardGames.owned')}
                                </Button>
                            </div>
                        </div>
                    );
                })}
            </div>

            <div className="flex flex-col gap-2 border-t pt-4">
                <Label htmlFor="manual-game-name">{t('boardGames.addManually')}</Label>
                <Input
                    id="manual-game-name"
                    value={manualName}
                    onChange={(e) => setManualName(e.target.value)}
                    placeholder={t('boardGames.gameName')}
                />
                <div className="flex gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        className="flex-1"
                        disabled={addingManual || manualName.trim() === ''}
                        onClick={() => addManual('wishlist')}
                    >
                        {t('boardGames.wishlist')}
                    </Button>
                    <Button size="sm" className="flex-1" disabled={addingManual || manualName.trim() === ''} onClick={() => addManual('owned')}>
                        {t('boardGames.owned')}
                    </Button>
                </div>
            </div>

            {/* Required by BGG's XML API terms for public-facing applications
                ("Using other parts of our API" / "Application Tokens" section). */}
            <a
                href="https://boardgamegeek.com"
                target="_blank"
                rel="noopener noreferrer"
                className="text-muted-foreground self-center text-xs underline"
            >
                {t('boardGames.poweredByBgg')}
            </a>
        </div>
    );
}
