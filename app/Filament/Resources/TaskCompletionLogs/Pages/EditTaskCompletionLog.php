<?php

namespace App\Filament\Resources\TaskCompletionLogs\Pages;

use App\Filament\Resources\TaskCompletionLogs\TaskCompletionLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaskCompletionLog extends EditRecord
{
    protected static string $resource = TaskCompletionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
