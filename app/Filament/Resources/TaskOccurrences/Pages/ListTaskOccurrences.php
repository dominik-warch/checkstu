<?php

namespace App\Filament\Resources\TaskOccurrences\Pages;

use App\Filament\Resources\TaskOccurrences\TaskOccurrenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaskOccurrences extends ListRecords
{
    protected static string $resource = TaskOccurrenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
