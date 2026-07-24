<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\Priority;
use App\Enums\RecurrenceType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('priority')
                    ->options(Priority::class)
                    ->default(1)
                    ->required(),
                Toggle::make('is_private')
                    ->required(),
                Select::make('default_assignee_id')
                    ->relationship('defaultAssignee', 'name')
                    ->searchable(),
                Select::make('categories')
                    ->relationship('categories', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),
                Select::make('recurrence_type')
                    ->options(RecurrenceType::class)
                    ->default('one_off')
                    ->required(),
                TextInput::make('rrule'),
                DatePicker::make('anchor_date'),
                TextInput::make('relative_interval_days')
                    ->numeric(),
                DatePicker::make('recurrence_ends_on'),
                Toggle::make('is_active')
                    ->required(),
                Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->searchable(),
            ]);
    }
}
