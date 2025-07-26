<?php

namespace App\Filament\Widgets;

use App\Services\FinancialReportService; // Importa o service
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class VendasGeraisStats extends BaseWidget
{
    // Oculta o widget de usuários que não sejam ADMIN ou DIRETOR
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['ADMIN', 'DIRETOR']);
    }
    
    protected function getStats(): array
    {
        // Cria uma instância do service para usar seus métodos
        $reportService = app(FinancialReportService::class);

        // Define um período padrão (ex: Mês Atual) para os KPIs
        $filters = [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ];
        $reportService->setFilters($filters);
        
        // Busca os dados usando os métodos que já existem no service
        $overviewData = $reportService->getOverviewData();
        $grandTotals = $overviewData['grandTotals'];

        return [
            Stat::make('Total de Gigs (Mês)', $grandTotals['gig_count'] ?? 0)
                ->description('Total de eventos no mês corrente')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Cachê Bruto Total (Mês)', 'R$ ' . number_format($grandTotals['cache_bruto_brl'] ?? 0, 2, ',', '.'))
                ->description('Soma dos valores de contrato em BRL')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Comissão Líquida Agência (Mês)', 'R$ ' . number_format($grandTotals['comissao_agencia_liquida_brl'] ?? 0, 2, ',', '.'))
                ->description('Resultado líquido para a agência')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('info'),
        ];
    }
}