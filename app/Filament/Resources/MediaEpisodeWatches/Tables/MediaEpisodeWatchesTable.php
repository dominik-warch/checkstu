<?php

namespace App\Filament\Resources\MediaEpisodeWatches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaEpisodeWatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('episode.season.mediaItem.title_de')
                    ->label('Show')
                    ->searchable(),
                TextColumn::make('episode.season.season_number')
                    ->label('Season')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('episode.episode_number')
                    ->label('Episode')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('episode.name')
                    ->label('Episode title')
                    ->searchable(),
                TextColumn::make('watched_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
