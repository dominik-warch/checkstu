<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BoardGames\SearchBoardGamesAction;
use App\Support\Bgg\BggRequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardGameSearchController extends Controller
{
    public function index(Request $request, SearchBoardGamesAction $action): JsonResponse
    {
        $query = trim($request->string('query')->toString());

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        try {
            $results = $action->handle($query);
        } catch (BggRequestException) {
            // Distinct from an empty result set: the frontend shouldn't tell the user their
            // search came up empty when the actual cause is BGG being unreachable/blocking
            // this network (mirrors BookSearchController's Open Library handling).
            return response()->json(['results' => [], 'unavailable' => true], 503);
        }

        return response()->json(['results' => $results]);
    }
}
