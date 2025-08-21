<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel de Controle';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        $user = auth()->user();
        $widgets = [];

        // Widget que todos os usuários que acessam o dashboard veem
        $widgets[] = Widgets\AccountWidget::class;

        // ***** INÍCIO DA REFAFORAÇÃO *****
        // Widgets para ADMIN e DIRETOR (lógica combinada)
        if ($user->hasAnyRole(['ADMIN', 'DIRETOR'])) {
            $widgets[] = Widgets\VendasGeraisStats::class;
            $widgets[] = Widgets\FaturamentoChart::class;
        }

        // Widget técnico que só o ADMIN vê
        if ($user->hasRole('ADMIN')) {
            $widgets[] = Widgets\FilamentInfoWidget::class;
        }
        // ***** FIM DA REFAFORAÇÃO *****

        // Não há widgets específicos para o Booker aqui, pois ele é redirecionado

        return $widgets;
    }
}
