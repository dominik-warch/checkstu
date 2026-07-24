<?php

namespace App\Filament\Resources\TaskRecurrenceDates\Pages;

use App\Filament\Resources\TaskRecurrenceDates\TaskRecurrenceDateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskRecurrenceDate extends CreateRecord
{
    protected static string $resource = TaskRecurrenceDateResource::class;
}
