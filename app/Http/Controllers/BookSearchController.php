<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Books\SearchBooksAction;
use App\Support\GoogleBooks\GoogleBooksRequestException;
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
            return response()->json(['results' => $action->handle($query)]);
        } catch (GoogleBooksRequestException) {
            // Distinct from an empty result set: the frontend shouldn't tell the user their
            // search came up empty when the actual cause is Google's API being unreachable.
            return response()->json(['results' => [], 'unavailable' => true], 503);
        }
    }
}
