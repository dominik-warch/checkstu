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

export interface MediaItemSummary {
    id: number;
    tmdb_id: number;
    type: MediaType;
    title_de: string;
    title_en: string;
    poster_path: string | null;
    release_date: string | null;
}

export interface MediaEntrySummary {
    id: number;
    status: WatchStatus;
    watched_at: string | null;
    media_item: MediaItemSummary;
}

export interface LibraryFilters {
    status: WatchStatus | 'all';
    type: MediaType | 'all';
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
    next_episode: { id: number; season_number: number; episode_number: number; name: string } | null;
}

export interface MediaComingUpItem {
    date: string;
    media_item: MediaItemSummary;
    episode: { season_number: number; episode_number: number; name: string } | null;
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
}
