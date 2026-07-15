<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MediaEntry;
use App\Support\MediaEntryPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaLibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = MediaEntry::query()
            ->where('user_id', $request->user()->id)
            ->with('mediaItem')
            ->latest()
            ->get()
            ->map(fn (MediaEntry $entry) => MediaEntryPresenter::toArray($entry))
            ->values();

        return Inertia::render('media/library', [
            'entries' => $entries,
        ]);
    }
}
