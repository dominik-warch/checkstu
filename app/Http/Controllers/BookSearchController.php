<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Books\SearchBooksAction;
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

        return response()->json(['results' => $action->handle($query)]);
    }
}
