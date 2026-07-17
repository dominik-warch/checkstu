export type MediaType = 'movie' | 'tv';
export type WatchStatus = 'watchlist' | 'watching' | 'completed';

export interface MediaSearchResult {
    tmdb_id: number;
    type: MediaType;
    title: string;
    original_title: string;
    overview: string;
    poster_path: string | null;
    release_date: string | null;
}

export interface BookSearchResult {
    google_books_id: string;
    title: string;
    authors: string;
    overview: string;
    thumbnail_url: string | null;
    published_date: string | null;
}

export interface SharedByMember {
    id: number;
    name: string;
    color: string | null;
}

export interface SharedByEntry extends SharedByMember {
    status: WatchStatus;
}

export interface MediaItemSummary {
    id: number;
    tmdb_id: number;
    type: MediaType;
    title_de: string;
    title_en: string;
    poster_path: string | null;
    release_date: string | null;
}

export interface BookItemSummary {
    id: number;
    google_books_id: string;
    title: string;
    authors: string | null;
    thumbnail_url: string | null;
    published_date: string | null;
}

export interface MediaEntrySummary {
    kind: 'media';
    id: number;
    status: WatchStatus;
    watched_at: string | null;
    media_item: MediaItemSummary;
    shared_by: SharedByMember[];
}

export interface BookEntrySummary {
    kind: 'book';
    id: number;
    status: WatchStatus;
    read_at: string | null;
    book_item: BookItemSummary;
    shared_by: SharedByMember[];
}

export type LibraryEntry = MediaEntrySummary | BookEntrySummary;

export interface LibraryFilters {
    status: WatchStatus | 'all';
    type: MediaType | 'book' | 'all';
}

export interface MediaSeasonSummary {
    id: number;
    season_number: number;
    name: string;
    episode_count: number;
    is_cached: boolean;
    watched_count: number;
}

export interface MediaEpisodeDetail {
    id: number;
    episode_number: number;
    name: string;
    air_date: string | null;
    watched: boolean;
}

export interface MediaNextEpisodeEntry {
    media_item: MediaItemSummary;
    next_episode: { id: number; season_number: number; episode_number: number; name: string };
}

export interface MediaComingUpItem {
    kind: 'media';
    date: string;
    media_item: MediaItemSummary;
    episode: { season_number: number; episode_number: number; name: string } | null;
}

export interface BookComingUpItem {
    kind: 'book';
    date: string;
    book_item: BookItemSummary;
}

export type ComingUpItem = MediaComingUpItem | BookComingUpItem;

export interface BookItemDetail {
    id: number;
    google_books_id: string;
    title: string;
    authors: string | null;
    overview: string | null;
    thumbnail_url: string | null;
    published_date: string | null;
    entry: { id: number; status: WatchStatus; read_at: string | null } | null;
    shared_by: SharedByEntry[];
}

export interface MediaItemDetail {
    id: number;
    tmdb_id: number;
    type: MediaType;
    title_de: string;
    title_en: string;
    overview: string;
    poster_path: string | null;
    release_date: string | null;
    tv_status: string | null;
    entry: { id: number; status: WatchStatus; watched_at: string | null } | null;
    seasons: MediaSeasonSummary[];
    shared_by: SharedByEntry[];
}
