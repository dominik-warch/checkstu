<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\SearchMediaAction;
use App\Enums\MediaType;
use App\Models\MediaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaSearchController extends Controller
{
    public function index(Request $request, SearchMediaAction $action): JsonResponse
    {
        $query = trim($request->string('query')->toString());
        $type = MediaType::tryFrom($request->string('type', 'movie')->toString()) ?? MediaType::Movie;

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $results = $action->handle($query, $type);

        // Search results are cached per query, shared across every user — "already in my
        // library" has to be layered on afterwards, per request, not baked into the cache.
        $ownedTmdbIds = MediaItem::where('type', $type)
            ->whereIn('tmdb_id', array_column($results, 'tmdb_id'))
            ->whereHas('entries', fn ($q) => $q->where('user_id', $request->user()->id))
            ->pluck('tmdb_id')
            ->all();

        $results = array_map(
            fn (array $result) => [...$result, 'in_library' => in_array($result['tmdb_id'], $ownedTmdbIds, true)],
            $results,
        );

        return response()->json(['results' => $results]);
    }
}
