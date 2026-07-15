/** Builds a full poster URL from TMDb's relative `poster_path` and the server-shared image base URL. */
export function posterUrl(baseUrl: string, path: string | null, size: 'w92' | 'w185' | 'w342' = 'w92'): string | null {
    return path ? `${baseUrl}/${size}${path}` : null;
}
