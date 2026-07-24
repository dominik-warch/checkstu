<?php

namespace App\Filament\Resources\BookEntries;

use App\Filament\Resources\BookEntries\Pages\CreateBookEntry;
use App\Filament\Resources\BookEntries\Pages\EditBookEntry;
use App\Filament\Resources\BookEntries\Pages\ListBookEntries;
use App\Filament\Resources\BookEntries\Schemas\BookEntryForm;
use App\Filament\Resources\BookEntries\Tables\BookEntriesTable;
use App\Models\BookEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookEntryResource extends Resource
{
    protected static ?string $model = BookEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BookEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookEntriesTable::configure($table);
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
            'index' => ListBookEntries::route('/'),
            'create' => CreateBookEntry::route('/create'),
            'edit' => EditBookEntry::route('/{record}/edit'),
        ];
    }
}
