<?php

namespace App\Filament\Resources\TaskCompletionLogs\Schemas;

use App\Enums\CompletionAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskCompletionLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('task_occurrence_id')
                    ->numeric(),
                Select::make('task_id')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable(),
                Select::make('acted_by_user_id')
                    ->relationship('actedBy', 'name')
                    ->searchable(),
                Select::make('action')
                    ->options(CompletionAction::class)
                    ->required(),
                DatePicker::make('due_date'),
            ]);
    }
}
