<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\VendasGeraisStats;
use App\Filament\Widgets\FaturamentoChart;
use App\Models\User;
use App\Filament\Widgets;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel de Controle';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $widgets = [];

        // Widgets para ADMIN e DIRETOR (lógica combinada)
        if ($user && $user->hasAnyRole(['ADMIN', 'DIRETOR'])) {
            $widgets[] = VendasGeraisStats::class;
            $widgets[] = FaturamentoChart::class;
        }

        return $widgets;
    }
}
