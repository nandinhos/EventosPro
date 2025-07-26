<?php

namespace App\Filament\Resources\BookerResource\Pages;

use App\Filament\Resources\BookerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooker extends EditRecord
{
    protected static string $resource = BookerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
