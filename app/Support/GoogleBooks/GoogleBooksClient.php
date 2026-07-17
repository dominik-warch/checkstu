<?php

declare(strict_types=1);

namespace App\Support\GoogleBooks;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Google Books v1 volumes API. Works unauthenticated
 * at low request volume — the API key is optional, unlike TMDb's.
 */
class GoogleBooksClient
{
    /**
     * @return list<array{google_books_id: string, title: string, authors: string, overview: string, thumbnail_url: ?string, published_date: ?string}>
     */
    public function search(string $query): array
    {
        $response = $this->get('volumes', ['q' => $query, 'maxResults' => 20]);

        return collect($response['items'] ?? [])
            ->map(fn (array $item) => $this->normalize($item))
            ->values()
            ->all();
    }

    /**
     * @return array{google_books_id: string, title: string, authors: string, overview: string, thumbnail_url: ?string, published_date: ?string}
     */
    public function details(string $volumeId): array
    {
        return $this->normalize($this->get("volumes/{$volumeId}"));
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{google_books_id: string, title: string, authors: string, overview: string, thumbnail_url: ?string, published_date: ?string}
     */
    private function normalize(array $item): array
    {
        $info = $item['volumeInfo'] ?? [];

        return [
            'google_books_id' => $item['id'],
            'title' => $info['title'] ?? '',
            'authors' => implode(', ', $info['authors'] ?? []),
            'overview' => $info['description'] ?? '',
            'thumbnail_url' => $this->secureUrl($info['imageLinks']['thumbnail'] ?? $info['imageLinks']['smallThumbnail'] ?? null),
            'published_date' => $this->normalizeDate($info['publishedDate'] ?? null),
        ];
    }

    /**
     * Google Books gives "YYYY", "YYYY-MM", or "YYYY-MM-DD". A bare 4-digit
     * year can't go through Carbon::parse() directly — PHP's date parser reads
     * it as a time ("19:55") rather than a year, silently returning today's
     * date instead. Normalize the two partial forms explicitly; only hand
     * genuinely full dates to Carbon, and guard against anything unparseable.
     */
    private function normalizeDate(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if (preg_match('/^\d{4}$/', $date) === 1) {
            return "{$date}-01-01";
        }

        if (preg_match('/^\d{4}-\d{2}$/', $date) === 1) {
            return "{$date}-01";
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Google Books returns http:// thumbnails — https-served pages would block them as mixed content. */
    private function secureUrl(?string $url): ?string
    {
        return $url !== null ? preg_replace('/^http:/', 'https:', $url) : null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query = []): array
    {
        // Google's Books "List"/search endpoint has a well-documented habit of returning
        // transient 503s with no relation to quota or query — two extra attempts turn most
        // of those into a success instead of a user-facing failure. 4xx isn't retried: those
        // are deterministic (bad key, bad request) and won't change on a second try.
        $response = Http::baseUrl(config('services.google_books.base_url'))
            ->retry(2, 250, fn (\Throwable $exception) => $exception instanceof RequestException && $exception->response->serverError(), throw: false)
            ->get($endpoint, array_filter([
                ...$query,
                'key' => config('services.google_books.key'),
            ]));

        if ($response->failed()) {
            throw new GoogleBooksRequestException("Google Books request to [{$endpoint}] failed with status {$response->status()}.");
        }

        return $response->json();
    }
}
