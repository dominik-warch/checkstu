<?php

namespace App\Filament\Resources\BookEntries\Pages;

use App\Filament\Resources\BookEntries\BookEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookEntries extends ListRecords
{
    protected static string $resource = BookEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
