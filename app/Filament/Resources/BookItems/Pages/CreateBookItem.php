<?php

namespace App\Filament\Resources\BookItems\Pages;

use App\Filament\Resources\BookItems\BookItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookItem extends CreateRecord
{
    protected static string $resource = BookItemResource::class;
}
