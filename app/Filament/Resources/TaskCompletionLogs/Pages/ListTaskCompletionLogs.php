<?php

namespace App\Filament\Resources\TaskCompletionLogs\Pages;

use App\Filament\Resources\TaskCompletionLogs\TaskCompletionLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaskCompletionLogs extends ListRecords
{
    protected static string $resource = TaskCompletionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
