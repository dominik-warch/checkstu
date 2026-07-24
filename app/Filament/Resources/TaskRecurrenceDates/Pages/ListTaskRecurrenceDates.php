<?php

namespace App\Filament\Resources\TaskRecurrenceDates\Pages;

use App\Filament\Resources\TaskRecurrenceDates\TaskRecurrenceDateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaskRecurrenceDates extends ListRecords
{
    protected static string $resource = TaskRecurrenceDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
