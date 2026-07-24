<?php

namespace App\Filament\Resources\TaskRecurrenceDates\Pages;

use App\Filament\Resources\TaskRecurrenceDates\TaskRecurrenceDateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaskRecurrenceDate extends EditRecord
{
    protected static string $resource = TaskRecurrenceDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
