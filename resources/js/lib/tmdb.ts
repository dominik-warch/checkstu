/**
 * Builds a full poster URL from a TMDb relative `poster_path` and the
 * server-shared image base URL. Book thumbnails (Google Books) come back as
 * already-absolute URLs — those pass through unchanged rather than getting
 * the TMDb base URL prepended.
 */
export function posterUrl(baseUrl: string, path: string | null, size: 'w92' | 'w185' | 'w342' = 'w92'): string | null {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return `${baseUrl}/${size}${path}`;
}
