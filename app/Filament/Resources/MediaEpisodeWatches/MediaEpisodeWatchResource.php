<?php

namespace App\Filament\Resources\MediaEpisodeWatches;

use App\Filament\Resources\MediaEpisodeWatches\Pages\CreateMediaEpisodeWatch;
use App\Filament\Resources\MediaEpisodeWatches\Pages\EditMediaEpisodeWatch;
use App\Filament\Resources\MediaEpisodeWatches\Pages\ListMediaEpisodeWatches;
use App\Filament\Resources\MediaEpisodeWatches\Schemas\MediaEpisodeWatchForm;
use App\Filament\Resources\MediaEpisodeWatches\Tables\MediaEpisodeWatchesTable;
use App\Models\MediaEpisodeWatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MediaEpisodeWatchResource extends Resource
{
    protected static ?string $model = MediaEpisodeWatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MediaEpisodeWatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaEpisodeWatchesTable::configure($table);
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
            'index' => ListMediaEpisodeWatches::route('/'),
            'create' => CreateMediaEpisodeWatch::route('/create'),
            'edit' => EditMediaEpisodeWatch::route('/{record}/edit'),
        ];
    }
}
