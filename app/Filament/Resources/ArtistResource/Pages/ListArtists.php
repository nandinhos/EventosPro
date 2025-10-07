<?php

namespace App\Filament\Resources\ArtistResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ArtistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArtists extends ListRecords
{
    protected static string $resource = ArtistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
