<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Books\SearchBooksAction;
use App\Models\BookItem;
use App\Support\OpenLibrary\OpenLibraryRequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookSearchController extends Controller
{
    public function index(Request $request, SearchBooksAction $action): JsonResponse
    {
        $query = trim($request->string('query')->toString());

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        try {
            $results = $action->handle($query);
        } catch (OpenLibraryRequestException) {
            // Distinct from an empty result set: the frontend shouldn't tell the user their
            // search came up empty when the actual cause is Open Library being unreachable.
            return response()->json(['results' => [], 'unavailable' => true], 503);
        }

        // Search results are cached per query, shared across every user — "already in my
        // library" has to be layered on afterwards, per request, not baked into the cache.
        $ownedIds = BookItem::whereIn('open_library_id', array_column($results, 'open_library_id'))
            ->whereHas('entries', fn ($q) => $q->where('user_id', $request->user()->id))
            ->pluck('open_library_id')
            ->all();

        $results = array_map(
            fn (array $result) => [...$result, 'in_library' => in_array($result['open_library_id'], $ownedIds, true)],
            $results,
        );

        return response()->json(['results' => $results]);
    }
}
