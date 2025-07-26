<?php

namespace App\Filament\Resources\BookerResource\Widgets;

use App\Models\Booker;
use App\Services\BookerFinancialsService; // Importa o service
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class BookerStatsOverview extends BaseWidget
{
    public ?Booker $record = null; // A página 'ViewBooker' injetará o booker aqui

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        // Reutiliza a lógica do seu service!
        $financialService = app(BookerFinancialsService::class);
        $salesKpis = $financialService->getSalesKpis($this->record, now()->subYear(), now());
        $commissionKpis = $financialService->getCommissionKpis($this->record);

        return [
            Stat::make('Total Vendido (12 meses)', 'R$ ' . number_format($salesKpis['total_sold_value'], 2, ',', '.'))
                ->description($salesKpis['total_gigs_sold'] . ' Gigs vendidas')
                ->color('success'),
            
            Stat::make('Comissão Recebida', 'R$ ' . number_format($commissionKpis['commission_received'], 2, ',', '.'))
                ->description('Total pago ao booker')
                ->color('info'),
            
            Stat::make('Comissão a Receber', 'R$ ' . number_format($commissionKpis['commission_to_receive'], 2, ',', '.'))
                ->description('Pendente de pagamento')
                ->color('warning'),
        ];
    }
}