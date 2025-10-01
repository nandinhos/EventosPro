<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MyCommissions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.pages.my-commissions';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 2;

    protected function getHeaderWidgets(): array
    {
        return [
            // Adicione widgets aqui se necessário
        ];
    }
}
