<?php

namespace App\Filament\Resources\BookEntries\Pages;

use App\Filament\Resources\BookEntries\BookEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookEntry extends CreateRecord
{
    protected static string $resource = BookEntryResource::class;
}
