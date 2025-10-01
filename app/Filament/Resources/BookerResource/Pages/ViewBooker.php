<?php

namespace App\Filament\Resources\BookerResource\Pages;

use App\Filament\Resources\BookerResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBooker extends ViewRecord
{
    protected static string $resource = BookerResource::class;

    // Remove o botão de edição padrão do Filament
    protected function getHeaderActions(): array
    {
        return [];
    }

    // Define quais widgets serão exibidos nesta página
    protected function getHeaderWidgets(): array
    {
        return [
            BookerResource\Widgets\BookerStatsOverview::class,
        ];
    }

    // Define quais widgets serão exibidos no corpo da página
    protected function getFooterWidgets(): array
    {
        return [
            BookerResource\Widgets\BookerCommissionsChart::class,
            BookerResource\Widgets\TopArtistsTable::class,
        ];
    }
}
