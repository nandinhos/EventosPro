<?php

namespace App\Filament\Widgets;

use App\Models\Gig;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FaturamentoChart extends ChartWidget
{
    protected static ?string $heading = 'Faturamento Mensal (Contratos Assinados/Concluídos)';
    protected int | string | array $columnSpan = 'full';
    
    // Oculta o widget de usuários que não sejam ADMIN ou DIRETOR
    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['ADMIN', 'DIRETOR']);
    }

    protected function getData(): array
    {
        // Lógica de busca de dados (pode ser movida para um service no futuro)
        $startDate = now()->subMonths(11)->startOfMonth();
        $endDate = now()->endOfMonth();

        $monthlyRevenueData = Gig::query()
            ->select(
                DB::raw("DATE_FORMAT(COALESCE(contract_date, gig_date), '%Y-%m') as month"),
                DB::raw("SUM(cache_value) as total_revenue") // Assumindo que cache_value_brl pode não existir em todas as gigs
            )
            ->whereBetween(DB::raw('COALESCE(contract_date, gig_date)'), [$startDate, $endDate])
            ->whereIn('contract_status', ['assinado', 'concluido'])
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->pluck('total_revenue', 'month'); // pluck cria um array [chave => valor]
        
        $labels = [];
        $data = [];
        $currentMonth = $startDate->copy();

        for ($i = 0; $i < 12; $i++) {
            $monthKey = $currentMonth->format('Y-m');
            $labels[] = $currentMonth->translatedFormat('M/y');
            $data[] = $monthlyRevenueData->get($monthKey, 0); // Usa 0 como default se o mês não tiver dados
            $currentMonth->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Faturamento (BRL)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.5)',
                    'borderColor' => 'rgb(99, 102, 241)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}