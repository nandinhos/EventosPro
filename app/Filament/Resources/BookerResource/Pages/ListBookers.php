<?php

namespace App\Filament\Resources\BookerResource\Pages;

use App\Filament\Resources\BookerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookers extends ListRecords
{
    protected static string $resource = BookerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
