<?php

namespace App\Filament\Resources\TaskCompletionLogs;

use App\Filament\Resources\TaskCompletionLogs\Pages\CreateTaskCompletionLog;
use App\Filament\Resources\TaskCompletionLogs\Pages\EditTaskCompletionLog;
use App\Filament\Resources\TaskCompletionLogs\Pages\ListTaskCompletionLogs;
use App\Filament\Resources\TaskCompletionLogs\Schemas\TaskCompletionLogForm;
use App\Filament\Resources\TaskCompletionLogs\Tables\TaskCompletionLogsTable;
use App\Models\TaskCompletionLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaskCompletionLogResource extends Resource
{
    protected static ?string $model = TaskCompletionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TaskCompletionLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaskCompletionLogsTable::configure($table);
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
            'index' => ListTaskCompletionLogs::route('/'),
            'create' => CreateTaskCompletionLog::route('/create'),
            'edit' => EditTaskCompletionLog::route('/{record}/edit'),
        ];
    }
}
