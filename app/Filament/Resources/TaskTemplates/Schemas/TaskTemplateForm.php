<?php

namespace App\Filament\Resources\TaskTemplates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('usage_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->searchable(),
            ]);
    }
}
