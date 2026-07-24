<?php

namespace App\Filament\Resources\TaskOccurrences\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaskOccurrenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('task_id')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->required(),
                DatePicker::make('due_date'),
                Select::make('assignee_id')
                    ->relationship('assignee', 'name')
                    ->searchable(),
                DateTimePicker::make('completed_at'),
                Select::make('completed_by')
                    ->relationship('completedBy', 'name')
                    ->searchable(),
                Toggle::make('is_skipped')
                    ->required(),
            ]);
    }
}
