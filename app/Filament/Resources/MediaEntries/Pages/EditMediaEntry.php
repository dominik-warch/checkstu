<?php

namespace App\Filament\Resources\MediaEntries\Pages;

use App\Filament\Resources\MediaEntries\MediaEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaEntry extends EditRecord
{
    protected static string $resource = MediaEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
