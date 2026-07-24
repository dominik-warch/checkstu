<?php

namespace App\Filament\Resources\MediaEntries\Pages;

use App\Filament\Resources\MediaEntries\MediaEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaEntries extends ListRecords
{
    protected static string $resource = MediaEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
