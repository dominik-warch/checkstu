<?php

namespace App\Filament\Resources\BookItems\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BookItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('open_library_id')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('authors'),
                Textarea::make('overview')
                    ->columnSpanFull(),
                TextInput::make('thumbnail_url')
                    ->url(),
                DatePicker::make('published_date'),
            ]);
    }
}
