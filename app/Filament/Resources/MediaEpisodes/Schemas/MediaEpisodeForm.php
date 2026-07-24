<?php

namespace App\Filament\Resources\MediaEpisodes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaEpisodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('media_season_id')
                    ->relationship('season', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('tmdb_episode_id')
                    ->required()
                    ->numeric(),
                TextInput::make('episode_number')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                DatePicker::make('air_date'),
            ]);
    }
}
