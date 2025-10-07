<?php

namespace App\Filament\Resources\BookerResource\Widgets;

use App\Models\Booker;
use App\Services\BookerFinancialsService;
use Filament\Widgets\ChartWidget;

class BookerCommissionsChart extends ChartWidget
{
    protected ?string $heading = 'Evolução de Comissões Pagas (Últimos 12 Meses)';

    public ?Booker $record = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        if (! $this->record) {
            return [];
        }
        $financialService = app(BookerFinancialsService::class);
        $chartData = $financialService->getCommissionChartData($this->record);

        return [
            'datasets' => [
                [
                    'label' => 'Comissão Paga (R$)',
                    'data' => $chartData['data'],
                    'backgroundColor' => 'rgba(99, 102, 241, 0.2)',
                    'borderColor' => '#6366f1',
                ],
            ],
            'labels' => $chartData['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
