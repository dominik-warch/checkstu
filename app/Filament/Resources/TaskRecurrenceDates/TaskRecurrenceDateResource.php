<?php

namespace App\Filament\Resources\TaskRecurrenceDates;

use App\Filament\Resources\TaskRecurrenceDates\Pages\CreateTaskRecurrenceDate;
use App\Filament\Resources\TaskRecurrenceDates\Pages\EditTaskRecurrenceDate;
use App\Filament\Resources\TaskRecurrenceDates\Pages\ListTaskRecurrenceDates;
use App\Filament\Resources\TaskRecurrenceDates\Schemas\TaskRecurrenceDateForm;
use App\Filament\Resources\TaskRecurrenceDates\Tables\TaskRecurrenceDatesTable;
use App\Models\TaskRecurrenceDate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaskRecurrenceDateResource extends Resource
{
    protected static ?string $model = TaskRecurrenceDate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TaskRecurrenceDateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaskRecurrenceDatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaskRecurrenceDates::route('/'),
            'create' => CreateTaskRecurrenceDate::route('/create'),
            'edit' => EditTaskRecurrenceDate::route('/{record}/edit'),
        ];
    }
}
