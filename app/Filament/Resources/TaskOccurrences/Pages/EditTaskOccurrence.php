<?php

namespace App\Filament\Resources\TaskOccurrences\Pages;

use App\Filament\Resources\TaskOccurrences\TaskOccurrenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaskOccurrence extends EditRecord
{
    protected static string $resource = TaskOccurrenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
