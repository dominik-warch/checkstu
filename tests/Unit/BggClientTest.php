<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Bgg\BggClient;
use App\Support\Bgg\BggRequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BggClientTest extends TestCase
{
    private const SEARCH_XML = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <items total="1" termsofuse="https://boardgamegeek.com/xmlapi/termsofuse">
        <item type="boardgame" id="13">
        <name type="primary" value="CATAN" />
        <yearpublished value="1995" />
        </item>
        </items>
        XML;

    private const THING_XML = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <items termsofuse="https://boardgamegeek.com/xmlapi/termsofuse">
        <item type="boardgame" id="13">
        <thumbnail>https://cf.geekdo-images.com/thumb.jpg</thumbnail>
        <image>https://cf.geekdo-images.com/original.jpg</image>
        <name type="primary" sortindex="1" value="Catan" />
        <name type="alternate" sortindex="1" value="Siedler von Catan" />
        <yearpublished value="1995" />
        <minplayers value="3" />
        <maxplayers value="4" />
        <playingtime value="120" />
        <minplaytime value="60" />
        <maxplaytime value="120" />
        </item>
        </items>
        XML;

    public function test_search_normalizes_results_and_picks_the_primary_name(): void
    {
        Http::fake(['*/search*' => Http::response(self::SEARCH_XML, 200, ['Content-Type' => 'application/xml'])]);

        $results = (new BggClient)->search('Catan');

        $this->assertSame([
            'bgg_id' => 13,
            'name' => 'CATAN',
            'year_published' => 1995,
        ], $results[0]);
    }

    public function test_details_parses_thumbnail_and_player_counts(): void
    {
        Http::fake(['*/thing*' => Http::response(self::THING_XML, 200, ['Content-Type' => 'application/xml'])]);

        $details = (new BggClient)->details(13);

        $this->assertSame([
            'bgg_id' => 13,
            'name' => 'Catan',
            'thumbnail_url' => 'https://cf.geekdo-images.com/thumb.jpg',
            'year_published' => 1995,
            'min_players' => 3,
            'max_players' => 4,
            'playtime_minutes' => 120,
        ], $details);
    }

    public function test_details_throws_when_bgg_has_no_such_item(): void
    {
        $empty = <<<'XML'
            <?xml version="1.0" encoding="utf-8"?>
            <items termsofuse="https://boardgamegeek.com/xmlapi/termsofuse" />
            XML;

        Http::fake(['*/thing*' => Http::response($empty, 200, ['Content-Type' => 'application/xml'])]);

        $this->expectException(BggRequestException::class);

        (new BggClient)->details(999999);
    }

    public function test_a_failed_response_throws(): void
    {
        Http::fake(['*/search*' => Http::response('', 500)]);

        $this->expectException(BggRequestException::class);

        (new BggClient)->search('anything');
    }

    public function test_a_configured_token_is_sent_as_a_bearer_authorization_header(): void
    {
        config(['services.bgg.token' => 'e3f8c3ff-9926-4efc-863c-3b92acda4d32']);
        Http::fake(['*/search*' => Http::response(self::SEARCH_XML, 200, ['Content-Type' => 'application/xml'])]);

        (new BggClient)->search('Catan');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer e3f8c3ff-9926-4efc-863c-3b92acda4d32'));
    }

    public function test_no_authorization_header_is_sent_without_a_configured_token(): void
    {
        config(['services.bgg.token' => null]);
        Http::fake(['*/search*' => Http::response(self::SEARCH_XML, 200, ['Content-Type' => 'application/xml'])]);

        (new BggClient)->search('Catan');

        Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization'));
    }
}
