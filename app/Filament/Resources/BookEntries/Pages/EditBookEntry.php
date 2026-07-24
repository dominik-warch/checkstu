<?php

namespace App\Filament\Resources\BookEntries\Pages;

use App\Filament\Resources\BookEntries\BookEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookEntry extends EditRecord
{
    protected static string $resource = BookEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
