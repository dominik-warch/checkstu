<?php

namespace App\Filament\Resources\TaskCompletionLogs\Pages;

use App\Filament\Resources\TaskCompletionLogs\TaskCompletionLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskCompletionLog extends CreateRecord
{
    protected static string $resource = TaskCompletionLogResource::class;
}
