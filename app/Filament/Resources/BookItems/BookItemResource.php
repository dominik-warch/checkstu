<?php

namespace App\Filament\Resources\BookItems;

use App\Filament\Resources\BookItems\Pages\CreateBookItem;
use App\Filament\Resources\BookItems\Pages\EditBookItem;
use App\Filament\Resources\BookItems\Pages\ListBookItems;
use App\Filament\Resources\BookItems\Schemas\BookItemForm;
use App\Filament\Resources\BookItems\Tables\BookItemsTable;
use App\Models\BookItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookItemResource extends Resource
{
    protected static ?string $model = BookItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BookItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookItemsTable::configure($table);
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
            'index' => ListBookItems::route('/'),
            'create' => CreateBookItem::route('/create'),
            'edit' => EditBookItem::route('/{record}/edit'),
        ];
    }
}
