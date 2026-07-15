<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Support\MediaEntryPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaLibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $status = WatchStatus::tryFrom($request->string('status', '')->toString());
        $type = MediaType::tryFrom($request->string('type', '')->toString());

        $entries = MediaEntry::query()
            ->where('user_id', $request->user()->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($type, fn ($query) => $query->whereHas('mediaItem', fn ($q) => $q->where('type', $type)))
            ->with('mediaItem')
            ->latest()
            ->get()
            ->map(fn (MediaEntry $entry) => MediaEntryPresenter::toArray($entry))
            ->values();

        return Inertia::render('media/library', [
            'entries' => $entries,
            'filters' => [
                'status' => $status?->value ?? 'all',
                'type' => $type?->value ?? 'all',
            ],
        ]);
    }
}
