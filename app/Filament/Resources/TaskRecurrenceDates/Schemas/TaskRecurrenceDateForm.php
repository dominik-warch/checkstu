<?php

namespace App\Filament\Resources\TaskRecurrenceDates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaskRecurrenceDateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('task_id')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->required(),
                DatePicker::make('due_on')
                    ->required(),
                Toggle::make('is_consumed')
                    ->required(),
            ]);
    }
}
