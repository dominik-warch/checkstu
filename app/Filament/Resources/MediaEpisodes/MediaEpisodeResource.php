<?php

namespace App\Filament\Resources\MediaEpisodes;

use App\Filament\Resources\MediaEpisodes\Pages\CreateMediaEpisode;
use App\Filament\Resources\MediaEpisodes\Pages\EditMediaEpisode;
use App\Filament\Resources\MediaEpisodes\Pages\ListMediaEpisodes;
use App\Filament\Resources\MediaEpisodes\Schemas\MediaEpisodeForm;
use App\Filament\Resources\MediaEpisodes\Tables\MediaEpisodesTable;
use App\Models\MediaEpisode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MediaEpisodeResource extends Resource
{
    protected static ?string $model = MediaEpisode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MediaEpisodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaEpisodesTable::configure($table);
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
            'index' => ListMediaEpisodes::route('/'),
            'create' => CreateMediaEpisode::route('/create'),
            'edit' => EditMediaEpisode::route('/{record}/edit'),
        ];
    }
}
