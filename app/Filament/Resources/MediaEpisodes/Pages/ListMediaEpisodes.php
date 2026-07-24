<?php

namespace App\Filament\Resources\MediaEpisodes\Pages;

use App\Filament\Resources\MediaEpisodes\MediaEpisodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaEpisodes extends ListRecords
{
    protected static string $resource = MediaEpisodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
