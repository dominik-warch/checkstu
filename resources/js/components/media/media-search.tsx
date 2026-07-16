import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import MediaPoster from '@/components/media/media-poster';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { t } from '@/lib/i18n';
import type { BookSearchResult, MediaSearchResult, MediaType } from '@/types/media';

type SearchType = MediaType | 'book';

interface MediaSearchProps {
    onAdded?: () => void;
}

const placeholder: Record<SearchType, string> = {
    movie: t('media.searchPlaceholderMovie'),
    tv: t('media.searchPlaceholderTv'),
    book: t('media.searchPlaceholderBook'),
};

/**
 * Debounced remote search — against our TMDb proxy (GET media.search) for
 * movies/TV, or our Google Books proxy (GET books.search) for books, never a
 * client-side filter over pre-loaded data like the closest existing precedent
 * (TitleAutocomplete), since results come from a remote, rate-limited API.
 */
export default function MediaSearch({ onAdded }: MediaSearchProps) {
    const [type, setType] = useState<SearchType>('movie');
    const [query, setQuery] = useState('');
    const [mediaResults, setMediaResults] = useState<MediaSearchResult[]>([]);
    const [bookResults, setBookResults] = useState<BookSearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(false);
    const [addingKey, setAddingKey] = useState<string | null>(null);

    useEffect(() => {
        const trimmed = query.trim();
        if (trimmed === '') {
            setMediaResults([]);
            setBookResults([]);
            setError(false);
            setLoading(false);
            return;
        }

        setLoading(true);
        const timeout = setTimeout(() => {
            const request =
                type === 'book'
                    ? fetch(route('books.search', { query: trimmed }), { headers: { Accept: 'application/json' } })
                          .then((res) => {
                              if (!res.ok) throw new Error('search failed');
                              return res.json();
                          })
                          .then((data: { results: BookSearchResult[] }) => setBookResults(data.results))
                    : fetch(route('media.search', { query: trimmed, type }), { headers: { Accept: 'application/json' } })
                          .then((res) => {
                              if (!res.ok) throw new Error('search failed');
                              return res.json();
                          })
                          .then((data: { results: MediaSearchResult[] }) => setMediaResults(data.results));

            request
                .then(() => setError(false))
                .catch(() => setError(true))
                .finally(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timeout);
    }, [query, type]);

    function addMedia(result: MediaSearchResult, status: 'watchlist' | 'completed') {
        const key = `${result.type}-${result.tmdb_id}`;
        setAddingKey(key);
        router.post(
            route('media.entries.store'),
            { tmdb_id: result.tmdb_id, type: result.type, status },
            { preserveScroll: true, onFinish: () => setAddingKey(null), onSuccess: () => onAdded?.() },
        );
    }

    function addBook(result: BookSearchResult, status: 'watchlist' | 'completed') {
        const key = `book-${result.google_books_id}`;
        setAddingKey(key);
        router.post(
            route('books.entries.store'),
            { google_books_id: result.google_books_id, status },
            { preserveScroll: true, onFinish: () => setAddingKey(null), onSuccess: () => onAdded?.() },
        );
    }

    const isEmpty = query.trim() !== '' && mediaResults.length === 0 && bookResults.length === 0;

    return (
        <div className="flex flex-col gap-4">
            <ToggleGroup type="single" value={type} onValueChange={(v) => v && setType(v as SearchType)}>
                <ToggleGroupItem value="movie">{t('media.typeMovie')}</ToggleGroupItem>
                <ToggleGroupItem value="tv">{t('media.typeTv')}</ToggleGroupItem>
                <ToggleGroupItem value="book">{t('media.typeBook')}</ToggleGroupItem>
            </ToggleGroup>

            <Input autoFocus autoComplete="off" value={query} onChange={(e) => setQuery(e.target.value)} placeholder={placeholder[type]} />

            {loading && <p className="text-muted-foreground text-sm">{t('common.loading')}</p>}
            {!loading && error && <p className="text-sm text-rose-600 dark:text-rose-400">{t('media.searchError')}</p>}
            {!loading && !error && isEmpty && <p className="text-muted-foreground text-sm">{t('media.searchEmpty')}</p>}

            <div className="flex flex-col gap-2">
                {type === 'book'
                    ? bookResults.map((result) => {
                          const key = `book-${result.google_books_id}`;
                          const busy = addingKey === key;

                          return (
                              <div key={key} className="flex items-center gap-3 rounded-lg border p-2">
                                  <MediaPoster path={result.thumbnail_url} alt={result.title} className="h-16 w-11" />
                                  <div className="min-w-0 flex-1">
                                      <p className="line-clamp-2 font-medium break-words">{result.title}</p>
                                      {result.authors && <p className="text-muted-foreground truncate text-xs">{result.authors}</p>}
                                  </div>
                                  <div className="flex shrink-0 flex-col gap-1">
                                      <Button size="sm" variant="outline" disabled={busy} onClick={() => addBook(result, 'watchlist')}>
                                          {t('media.addToWatchlist')}
                                      </Button>
                                      <Button size="sm" disabled={busy} onClick={() => addBook(result, 'completed')}>
                                          {t('media.markRead')}
                                      </Button>
                                  </div>
                              </div>
                          );
                      })
                    : mediaResults.map((result) => {
                          const key = `${result.type}-${result.tmdb_id}`;
                          const busy = addingKey === key;

                          return (
                              <div key={key} className="flex items-center gap-3 rounded-lg border p-2">
                                  <MediaPoster path={result.poster_path} alt={result.title} className="h-16 w-11" />
                                  <div className="min-w-0 flex-1">
                                      <p className="line-clamp-2 font-medium break-words">{result.title}</p>
                                      {result.release_date && <p className="text-muted-foreground text-xs">{result.release_date.slice(0, 4)}</p>}
                                  </div>
                                  <div className="flex shrink-0 flex-col gap-1">
                                      <Button size="sm" variant="outline" disabled={busy} onClick={() => addMedia(result, 'watchlist')}>
                                          {t('media.addToWatchlist')}
                                      </Button>
                                      {result.type === 'movie' && (
                                          <Button size="sm" disabled={busy} onClick={() => addMedia(result, 'completed')}>
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
