<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\SearchMediaAction;
use App\Enums\MediaType;
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

        return response()->json(['results' => $action->handle($query, $type)]);
    }
}
