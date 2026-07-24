<?php

namespace App\Filament\Resources\MediaEntries;

use App\Filament\Resources\MediaEntries\Pages\CreateMediaEntry;
use App\Filament\Resources\MediaEntries\Pages\EditMediaEntry;
use App\Filament\Resources\MediaEntries\Pages\ListMediaEntries;
use App\Filament\Resources\MediaEntries\Schemas\MediaEntryForm;
use App\Filament\Resources\MediaEntries\Tables\MediaEntriesTable;
use App\Models\MediaEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MediaEntryResource extends Resource
{
    protected static ?string $model = MediaEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MediaEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaEntriesTable::configure($table);
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
            'index' => ListMediaEntries::route('/'),
            'create' => CreateMediaEntry::route('/create'),
            'edit' => EditMediaEntry::route('/{record}/edit'),
        ];
    }
}
