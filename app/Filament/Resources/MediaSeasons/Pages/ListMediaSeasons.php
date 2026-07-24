<?php

namespace App\Filament\Resources\MediaSeasons\Pages;

use App\Filament\Resources\MediaSeasons\MediaSeasonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaSeasons extends ListRecords
{
    protected static string $resource = MediaSeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
