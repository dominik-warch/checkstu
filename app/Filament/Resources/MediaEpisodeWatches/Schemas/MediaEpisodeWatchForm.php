<?php

namespace App\Filament\Resources\MediaEpisodeWatches\Schemas;

use App\Models\MediaEpisode;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class MediaEpisodeWatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Select::make('media_episode_id')
                    ->relationship('episode', 'name', fn ($query) => $query->with('season.mediaItem'))
                    ->getOptionLabelFromRecordUsing(
                        fn (MediaEpisode $episode) => "{$episode->season->mediaItem->title_de} · S{$episode->season->season_number}E{$episode->episode_number} · {$episode->name}",
                    )
                    ->searchable()
                    ->required(),
                DatePicker::make('watched_at'),
            ]);
    }
}
