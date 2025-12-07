<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Gig;
use App\Models\Settlement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service para calcular e agregar as métricas financeiras de um Artista.
 */
class ArtistFinancialsService
{
    protected GigFinancialCalculatorService $gigCalculator;

    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
    }

    /**
     * Calcula as principais métricas financeiras para um artista.
     *
     * @param  Collection|null  $gigs  Uma coleção de gigs pré-filtradas (opcional, para performance).
     */
    public function getFinancialMetrics(Artist $artist, ?Collection $gigs = null): array
    {
        // Se uma coleção de gigs não for passada, busca todas as gigs do artista.
        // Isso permite reutilizar a lógica se já tivermos as gigs filtradas no controller.
        if (is_null($gigs)) {
            $gigs = $artist->gigs()->get();
        }

        $totalGigs = $gigs->count();
        $cacheReceivedBrl = 0;
        $cachePendingBrl = 0;
        $totalGrossFee = 0;
        $realizedGigsCount = 0;

        foreach ($gigs as $gig) {
            // Utiliza o service central para obter o valor líquido do artista para esta gig.
            // Isso garante que a regra de negócio (Cachê Bruto - Comissão Agência) seja sempre a mesma.
            $artistNetPayout = $this->gigCalculator->calculateArtistNetPayoutBrl($gig);
            $totalGrossFee += $this->gigCalculator->calculateGrossCashBrl($gig);

            if ($gig->artist_payment_status === 'pago') {
                $cacheReceivedBrl += $artistNetPayout;
            } else { // 'pendente' ou qualquer outro status
                $cachePendingBrl += $artistNetPayout;
            }

            // Conta gigs realizadas (passadas)
            if ($gig->gig_date <= Carbon::today()) {
                $realizedGigsCount++;
            }
        }

        // Calcula taxa média por show (apenas dos realizados)
        $averageFee = $realizedGigsCount > 0 ? ($cacheReceivedBrl + $cachePendingBrl) / $realizedGigsCount : 0;

        return [
            'total_gigs' => $totalGigs,
            'realized_gigs' => $realizedGigsCount,
            'future_gigs' => $totalGigs - $realizedGigsCount,
            'cache_received_brl' => $cacheReceivedBrl,
            'cache_pending_brl' => $cachePendingBrl,
            'totalGrossFee' => $totalGrossFee,
            'average_fee' => $averageFee,
        ];
    }

    /**
     * Calcula dados de performance mensal para gráficos.
     *
     * @return array Dados formatados para Chart.js
     */
    public function getMonthlyPerformance(Collection $gigs, Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $received = [];
        $pending = [];
        $gigCount = [];

        // Cria um período de meses
        $current = $startDate->copy()->startOfMonth();
        $end = $endDate->copy()->endOfMonth();

        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            $monthLabel = $current->isoFormat('MMM/YY');

            $labels[] = $monthLabel;
            $received[$monthKey] = 0;
            $pending[$monthKey] = 0;
            $gigCount[$monthKey] = 0;

            $current->addMonth();
        }

        // Agrupa gigs por mês
        foreach ($gigs as $gig) {
            $monthKey = $gig->gig_date->format('Y-m');

            if (! isset($received[$monthKey])) {
                continue; // Gig fora do período
            }

            $artistNetPayout = $this->gigCalculator->calculateArtistNetPayoutBrl($gig);
            $gigCount[$monthKey]++;

            if ($gig->artist_payment_status === 'pago') {
                $received[$monthKey] += $artistNetPayout;
            } else {
                $pending[$monthKey] += $artistNetPayout;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Recebido',
                    'data' => array_values($received),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Pendente',
                    'data' => array_values($pending),
                    'backgroundColor' => 'rgba(234, 179, 8, 0.7)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'borderWidth' => 1,
                ],
            ],
            'gigCount' => array_values($gigCount),
        ];
    }

    /**
     * Calcula métricas de fechamento/settlement para o artista.
     */
    public function getSettlementMetrics(Collection $gigs): array
    {
        $stages = [
            'aguardando_conferencia' => 0,
            'fechamento_enviado' => 0,
            'documentacao_recebida' => 0,
            'pago' => 0,
        ];

        foreach ($gigs as $gig) {
            // Só conta gigs realizadas (passadas)
            if ($gig->gig_date > Carbon::today()) {
                continue;
            }

            $stage = $gig->settlement?->settlement_stage ?? Settlement::STAGE_AGUARDANDO_CONFERENCIA;
            
            if (isset($stages[$stage])) {
                $stages[$stage]++;
            }
        }

        $totalRealized = array_sum($stages);

        return [
            'stages' => $stages,
            'total_realized' => $totalRealized,
            'completed_percentage' => $totalRealized > 0 
                ? round(($stages['pago'] / $totalRealized) * 100) 
                : 0,
        ];
    }
}

