<?php

namespace App\Filament\Resources\MediaSeasons;

use App\Filament\Resources\MediaSeasons\Pages\CreateMediaSeason;
use App\Filament\Resources\MediaSeasons\Pages\EditMediaSeason;
use App\Filament\Resources\MediaSeasons\Pages\ListMediaSeasons;
use App\Filament\Resources\MediaSeasons\Schemas\MediaSeasonForm;
use App\Filament\Resources\MediaSeasons\Tables\MediaSeasonsTable;
use App\Models\MediaSeason;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MediaSeasonResource extends Resource
{
    protected static ?string $model = MediaSeason::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MediaSeasonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaSeasonsTable::configure($table);
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
            'index' => ListMediaSeasons::route('/'),
            'create' => CreateMediaSeason::route('/create'),
            'edit' => EditMediaSeason::route('/{record}/edit'),
        ];
    }
}
