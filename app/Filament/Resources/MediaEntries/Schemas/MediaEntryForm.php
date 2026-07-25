<?php

namespace App\Filament\Resources\MediaEntries\Schemas;

use App\Enums\WatchStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class MediaEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Select::make('media_item_id')
                    ->relationship('mediaItem', 'title_de')
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->options(WatchStatus::class)
                    ->required(),
            ]);
    }
}
