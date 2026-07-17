<?php

declare(strict_types=1);

namespace App\Support\OpenLibrary;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the free, key-less Open Library API. Chosen over Google
 * Books after that integration turned out to have a persistently flaky search
 * endpoint (transient 503s unrelated to quota or query) — see git history.
 *
 * The trade-off: a book's data is split across three separate Open Library
 * resources instead of Google's one. A search hit only ever has a "work"
 * (e.g. the English original of a translated novel) plus, when Open Library
 * matched a specific printing, an "edition" nested under it — which is what
 * carries a title in the language actually searched for. Descriptions and
 * author *references* live on the work; author *names* live one level up
 * again, on the author resource itself.
 */
class OpenLibraryClient
{
    /**
     * @return list<array{open_library_id: string, title: string, authors: string, overview: string, thumbnail_url: ?string, published_date: ?string}>
     */
    public function search(string $query): array
    {
        $response = $this->get('search.json', [
            'q' => $query,
            'fields' => 'key,title,author_name,cover_i,editions',
            'limit' => 20,
        ]);

        return collect($response['docs'] ?? [])
            ->map(fn (array $doc) => $this->normalizeSearchDoc($doc))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Fetches authoritative details for one specific edition (not the
     * client-supplied search result) — an edition key such as
     * "/books/OL57502463M" identifies one printing/translation, the thing a
     * title is correctly localized for. Three sequential Open Library calls
     * (edition, its work, the work's author) to build one full record; only
     * paid once, at add time, and cached forever afterwards in book_items.
     *
     * @return array{open_library_id: string, title: string, authors: string, overview: string, thumbnail_url: ?string, published_date: ?string}
     */
    public function details(string $editionKey): array
    {
        $edition = $this->get("{$editionKey}.json");
        $work = $this->workFor($edition);

        return [
            'open_library_id' => $editionKey,
            'title' => $edition['title'] ?? $work['title'] ?? '',
            'authors' => $this->authorNames($work['authors'] ?? []),
            'overview' => $this->extractDescription($work['description'] ?? null),
            'thumbnail_url' => $this->coverUrl($edition['covers'][0] ?? $work['covers'][0] ?? null),
            'published_date' => $this->normalizePublishDate($edition['publish_date'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $doc
     * @return array{open_library_id: string, title: string, authors: string, overview: string, thumbnail_url: ?string, published_date: ?string}|null
     */
    private function normalizeSearchDoc(array $doc): ?array
    {
        $edition = $doc['editions']['docs'][0] ?? null;
        if (! is_array($edition) || ! isset($edition['key'])) {
            // No specific printing matched the query — nothing to build a stable,
            // correctly-titled id from. Empirically rare; skipped rather than
            // falling back to the work's (often differently-titled) own key.
            return null;
        }

        return [
            'open_library_id' => $edition['key'],
            'title' => $edition['title'] ?? $doc['title'] ?? '',
            'authors' => implode(', ', $doc['author_name'] ?? []),
            'overview' => '', // only ever on the work resource — details() fills this in once added
            'thumbnail_url' => $this->coverUrl($edition['cover_i'] ?? $doc['cover_i'] ?? null),
            'published_date' => null, // search docs carry no edition-specific date either
        ];
    }

    /**
     * @param  array<string, mixed>  $edition
     * @return array<string, mixed>
     */
    private function workFor(array $edition): array
    {
        $workKey = $edition['works'][0]['key'] ?? null;

        return $workKey !== null ? $this->get("{$workKey}.json") : [];
    }

    /**
     * @param  list<array{author?: array{key?: string}}>  $authorRefs
     */
    private function authorNames(array $authorRefs): string
    {
        $names = collect($authorRefs)
            ->map(fn (array $ref) => $ref['author']['key'] ?? null)
            ->filter()
            ->map(fn (string $authorKey) => $this->get("{$authorKey}.json")['name'] ?? null)
            ->filter()
            ->values()
            ->all();

        return implode(', ', $names);
    }

    private function extractDescription(string|array|null $description): string
    {
        if (is_array($description)) {
            return $description['value'] ?? '';
        }

        return $description ?? '';
    }

    private function coverUrl(?int $coverId): ?string
    {
        return $coverId !== null ? "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg" : null;
    }

    /**
     * Open Library's edition publish_date is free text ("01.03.2018", "2018",
     * "March 2018", ...) with no fixed format, unlike Google Books' documented
     * YYYY/YYYY-MM/YYYY-MM-DD — only a bare 4-digit year is safe to trust here;
     * anything else is dropped rather than risk guessing wrong.
     */
    private function normalizePublishDate(?string $date): ?string
    {
        if ($date !== null && preg_match('/(\d{4})/', $date, $matches) === 1) {
            return "{$matches[1]}-01-01";
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $endpoint, array $query = []): array
    {
        // Open Library has no documented flakiness like Google Books did, but the
        // retry is cheap defensive consistency now that three of these might chain
        // together in a single details() call.
        $response = Http::baseUrl(config('services.open_library.base_url'))
            ->retry(2, 250, fn (\Throwable $exception) => $exception instanceof RequestException && $exception->response->serverError(), throw: false)
            ->get($endpoint, $query);

        if ($response->failed()) {
            throw new OpenLibraryRequestException("Open Library request to [{$endpoint}] failed with status {$response->status()}.");
        }

        return $response->json();
    }
}
