<?php

namespace App\Filament\Resources\MediaEpisodeWatches\Pages;

use App\Filament\Resources\MediaEpisodeWatches\MediaEpisodeWatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaEpisodeWatch extends EditRecord
{
    protected static string $resource = MediaEpisodeWatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
