<?php

namespace App\Filament\Resources\GigResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\GigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGig extends EditRecord
{
    protected static string $resource = GigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
