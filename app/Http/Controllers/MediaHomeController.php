<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\MediaHomePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaHomeController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('media/home', [
            'nextEpisodes' => MediaHomePresenter::nextEpisodes($request->user()),
        ]);
    }
}
