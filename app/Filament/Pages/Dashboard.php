<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel de Controle';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $widgets = [];

        // Widgets para ADMIN e DIRETOR (lógica combinada)
        if ($user && $user->hasAnyRole(['ADMIN', 'DIRETOR'])) {
            $widgets[] = Widgets\VendasGeraisStats::class;
            $widgets[] = Widgets\FaturamentoChart::class;
        }

        return $widgets;
    }
}
