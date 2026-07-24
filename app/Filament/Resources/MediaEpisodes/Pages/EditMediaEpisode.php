<?php

namespace App\Filament\Resources\MediaEpisodes\Pages;

use App\Filament\Resources\MediaEpisodes\MediaEpisodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaEpisode extends EditRecord
{
    protected static string $resource = MediaEpisodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
