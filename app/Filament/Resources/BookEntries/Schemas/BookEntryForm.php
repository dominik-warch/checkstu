<?php

namespace App\Filament\Resources\BookEntries\Schemas;

use App\Enums\WatchStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class BookEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Select::make('book_item_id')
                    ->relationship('bookItem', 'title')
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->options(WatchStatus::class)
                    ->required(),
                DatePicker::make('read_at'),
            ]);
    }
}
