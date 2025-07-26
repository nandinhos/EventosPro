<?php

namespace App\Filament\Resources\BookerResource\Pages;

use App\Filament\Resources\BookerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBooker extends CreateRecord
{
    protected static string $resource = BookerResource::class;
}
