<?php

namespace App\Filament\Resources\BookerResource\Pages;

use App\Filament\Resources\BookerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookers extends ListRecords
{
    protected static string $resource = BookerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
