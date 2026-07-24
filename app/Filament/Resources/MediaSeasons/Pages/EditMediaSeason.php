<?php

namespace App\Filament\Resources\MediaSeasons\Pages;

use App\Filament\Resources\MediaSeasons\MediaSeasonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaSeason extends EditRecord
{
    protected static string $resource = MediaSeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
