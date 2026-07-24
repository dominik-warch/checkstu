<?php

namespace App\Filament\Resources\MediaEpisodeWatches\Pages;

use App\Filament\Resources\MediaEpisodeWatches\MediaEpisodeWatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaEpisodeWatches extends ListRecords
{
    protected static string $resource = MediaEpisodeWatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
