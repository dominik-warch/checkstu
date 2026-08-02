<?php

declare(strict_types=1);

namespace App\Support\Bgg;

use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

/**
 * Thin wrapper around BoardGameGeek's XML API v2 — no API key needed. Unlike
 * TmdbClient/GoogleBooksClient (JSON), BGG only ever speaks XML, so responses
 * are parsed with SimpleXML instead of Http::json(). Deliberately never
 * requests `stats=1` on the `thing` endpoint — that flag makes BGG queue
 * stats generation and can return a 202 needing a retry, which this domain
 * has no use for (no ratings/rankings in v1).
 */
class BggClient
{
    /**
     * @return list<array{bgg_id: int, name: string, year_published: ?int}>
     */
    public function search(string $query): array
    {
        $xml = $this->get('search', ['query' => $query, 'type' => 'boardgame']);

        $results = [];
        foreach ($xml->item as $item) {
            $name = $this->primaryName($item);

            if ($name === null) {
                continue;
            }

            $results[] = [
                'bgg_id' => (int) $item['id'],
                'name' => $name,
                'year_published' => $this->intValue($item, 'yearpublished'),
            ];
        }

        return $results;
    }

    /**
     * @return array{bgg_id: int, name: string, thumbnail_url: ?string, year_published: ?int, min_players: ?int, max_players: ?int, playtime_minutes: ?int}
     */
    public function details(int $bggId): array
    {
        $xml = $this->get('thing', ['id' => (string) $bggId]);

        if (! isset($xml->item)) {
            throw new BggRequestException("BGG has no item with id [{$bggId}].");
        }

        $item = $xml->item;
        $thumbnail = isset($item->thumbnail) ? trim((string) $item->thumbnail) : '';

        return [
            'bgg_id' => $bggId,
            'name' => $this->primaryName($item) ?? '',
            'thumbnail_url' => $thumbnail !== '' ? $thumbnail : null,
            'year_published' => $this->intValue($item, 'yearpublished'),
            'min_players' => $this->intValue($item, 'minplayers'),
            'max_players' => $this->intValue($item, 'maxplayers'),
            'playtime_minutes' => $this->intValue($item, 'playingtime'),
        ];
    }

    private function primaryName(SimpleXMLElement $item): ?string
    {
        foreach ($item->name as $name) {
            if ((string) $name['type'] === 'primary') {
                return (string) $name['value'];
            }
        }

        return null;
    }

    private function intValue(SimpleXMLElement $item, string $field): ?int
    {
        if (! isset($item->{$field})) {
            return null;
        }

        $value = (string) $item->{$field}['value'];

        return $value !== '' ? (int) $value : null;
    }

    /**
     * @param  array<string, string>  $query
     */
    private function get(string $endpoint, array $query): SimpleXMLElement
    {
        $response = Http::baseUrl(config('services.bgg.base_url'))->get($endpoint, $query);

        if ($response->failed()) {
            throw new BggRequestException("BGG request to [{$endpoint}] failed with status {$response->status()}.");
        }

        $xml = simplexml_load_string($response->body());

        if ($xml === false) {
            throw new BggRequestException("BGG response from [{$endpoint}] could not be parsed as XML.");
        }

        return $xml;
    }
}
