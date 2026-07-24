<?php

namespace App\Filament\Resources\MediaSeasons\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaSeasonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('media_item_id')
                    ->relationship('mediaItem', 'title_de')
                    ->searchable()
                    ->required(),
                TextInput::make('tmdb_season_id')
                    ->required()
                    ->numeric(),
                TextInput::make('season_number')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('episode_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DatePicker::make('air_date'),
                DateTimePicker::make('episodes_fetched_at'),
            ]);
    }
}
