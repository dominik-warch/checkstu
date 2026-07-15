<?php

declare(strict_types=1);

namespace App\Support\Tmdb;

use App\Enums\MediaType;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around TMDb's v3 REST API (api_key query-param auth). Only the
 * three endpoints the media-tracking domain needs — no general-purpose client.
 */
class TmdbClient
{
    /**
     * @return list<array{tmdb_id: int, type: string, title: string, original_title: string, overview: string, poster_path: ?string, release_date: ?string}>
     */
    public function search(string $query, MediaType $type): array
    {
        $endpoint = $type === MediaType::Movie ? 'search/movie' : 'search/tv';

        $response = $this->get($endpoint, ['query' => $query]);

        return collect($response['results'] ?? [])
            ->map(fn (array $result) => [
                'tmdb_id' => $result['id'],
                'type' => $type->value,
                'title' => $result[$type === MediaType::Movie ? 'title' : 'name'] ?? '',
                'original_title' => $result[$type === MediaType::Movie ? 'original_title' : 'original_name'] ?? '',
                'overview' => $result['overview'] ?? '',
                'poster_path' => $result['poster_path'] ?? null,
                'release_date' => $result[$type === MediaType::Movie ? 'release_date' : 'first_air_date'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * Full details for a single item, including both German and English
     * titles/overviews (via a single `append_to_response=translations` call)
     * and, for TV, the season list (summaries only — no episodes yet).
     *
     * @return array{
     *     tmdb_id: int,
     *     type: string,
     *     title_de: string,
     *     title_en: string,
     *     overview: string,
     *     poster_path: ?string,
     *     release_date: ?string,
     *     tv_status: ?string,
     *     seasons: list<array{tmdb_season_id: int, season_number: int, name: string, episode_count: int, air_date: ?string}>,
     * }
     */
    public function details(int $tmdbId, MediaType $type): array
    {
        $endpoint = $type === MediaType::Movie ? "movie/{$tmdbId}" : "tv/{$tmdbId}";

        $response = $this->get($endpoint, ['append_to_response' => 'translations']);

        [$titleDe, $overview] = $this->pickTranslation($response, $type, 'de');
        [$titleEn] = $this->pickTranslation($response, $type, 'en');

        $fallbackTitle = $response[$type === MediaType::Movie ? 'title' : 'name'] ?? '';

        $seasons = collect($response['seasons'] ?? [])
            ->reject(fn (array $season) => ($season['season_number'] ?? null) === null)
            ->map(fn (array $season) => [
                'tmdb_season_id' => $season['id'],
                'season_number' => $season['season_number'],
                'name' => $season['name'] ?? '',
                'episode_count' => $season['episode_count'] ?? 0,
                'air_date' => $season['air_date'] ?? null,
            ])
            ->values()
            ->all();

        return [
            'tmdb_id' => $tmdbId,
            'type' => $type->value,
            'title_de' => $titleDe !== '' ? $titleDe : $fallbackTitle,
            'title_en' => $titleEn !== '' ? $titleEn : $fallbackTitle,
            'overview' => $overview !== '' ? $overview : ($response['overview'] ?? ''),
            'poster_path' => $response['poster_path'] ?? null,
            'release_date' => $response[$type === MediaType::Movie ? 'release_date' : 'first_air_date'] ?? null,
            'tv_status' => $type === MediaType::Tv ? ($response['status'] ?? null) : null,
            'seasons' => $seasons,
        ];
    }

    /**
     * @return list<array{tmdb_episode_id: int, episode_number: int, name: string, air_date: ?string}>
     */
    public function seasonEpisodes(int $tmdbId, int $seasonNumber): array
    {
        $response = $this->get("tv/{$tmdbId}/season/{$seasonNumber}", ['language' => 'de-DE']);

        return collect($response['episodes'] ?? [])
            ->map(fn (array $episode) => [
                'tmdb_episode_id' => $episode['id'],
                'episode_number' => $episode['episode_number'],
                'name' => $episode['name'] ?? '',
                'air_date' => $episode['air_date'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query = []): array
    {
        $response = Http::baseUrl(config('services.tmdb.base_url'))
            ->get($endpoint, [
                ...$query,
                'api_key' => config('services.tmdb.key'),
                'language' => $query['language'] ?? 'de-DE',
            ]);

        if ($response->failed()) {
            throw new TmdbRequestException("TMDb request to [{$endpoint}] failed with status {$response->status()}.");
        }

        return $response->json();
    }

    /**
     * Pull a translated title+overview out of `append_to_response=translations`
     * for the given ISO 639-1 language code. Returns ['', ''] if TMDb has no
     * translation on file for that language (common for overviews).
     *
     * @return array{0: string, 1: string}
     */
    private function pickTranslation(array $response, MediaType $type, string $iso6391): array
    {
        $translation = collect($response['translations']['translations'] ?? [])
            ->first(fn (array $t) => $t['iso_639_1'] === $iso6391);

        if ($translation === null) {
            return ['', ''];
        }

        $data = $translation['data'] ?? [];
        $title = $data[$type === MediaType::Movie ? 'title' : 'name'] ?? '';

        return [$title, $data['overview'] ?? ''];
    }
}
