export type BoardGameStatus = 'owned' | 'wishlist';

export interface BoardGameSearchResult {
    bgg_id: number;
    name: string;
    year_published: number | null;
}

export interface BoardGame {
    id: number;
    bgg_id: number | null;
    name: string;
    thumbnail_url: string | null;
    year_published: number | null;
    min_players: number | null;
    max_players: number | null;
    playtime_minutes: number | null;
    status: BoardGameStatus;
    can: {
        delete: boolean;
    };
}

export interface BoardGameSummary {
    id: number;
    name: string;
}

export interface Member {
    id: number;
    name: string;
    color: string | null;
}

export interface BoardGamePlayPlayer {
    id: number;
    user_id: number | null;
    name: string;
    color: string | null;
    is_winner: boolean;
}

export interface BoardGamePlay {
    id: number;
    played_at: string;
    notes: string | null;
    board_game: {
        id: number;
        name: string;
        thumbnail_url: string | null;
    };
    players: BoardGamePlayPlayer[];
    can: {
        update: boolean;
        delete: boolean;
    };
}

export interface BoardGameCatalogueFilters {
    status: BoardGameStatus | 'all';
}
