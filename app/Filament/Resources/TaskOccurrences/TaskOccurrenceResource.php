<?php

namespace App\Filament\Resources\TaskOccurrences;

use App\Filament\Resources\TaskOccurrences\Pages\CreateTaskOccurrence;
use App\Filament\Resources\TaskOccurrences\Pages\EditTaskOccurrence;
use App\Filament\Resources\TaskOccurrences\Pages\ListTaskOccurrences;
use App\Filament\Resources\TaskOccurrences\Schemas\TaskOccurrenceForm;
use App\Filament\Resources\TaskOccurrences\Tables\TaskOccurrencesTable;
use App\Models\TaskOccurrence;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaskOccurrenceResource extends Resource
{
    protected static ?string $model = TaskOccurrence::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TaskOccurrenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaskOccurrencesTable::configure($table);
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
            'index' => ListTaskOccurrences::route('/'),
            'create' => CreateTaskOccurrence::route('/create'),
            'edit' => EditTaskOccurrence::route('/{record}/edit'),
        ];
    }
}
