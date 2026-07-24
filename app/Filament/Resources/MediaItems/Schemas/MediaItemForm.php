<?php

namespace App\Filament\Resources\MediaItems\Schemas;

use App\Enums\MediaType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tmdb_id')
                    ->required()
                    ->numeric(),
                Select::make('type')
                    ->options(MediaType::class)
                    ->required(),
                TextInput::make('title_de')
                    ->required(),
                TextInput::make('title_en')
                    ->required(),
                Textarea::make('overview')
                    ->columnSpanFull(),
                TextInput::make('poster_path'),
                DatePicker::make('release_date'),
                TextInput::make('tv_status'),
            ]);
    }
}
