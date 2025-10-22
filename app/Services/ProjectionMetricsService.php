<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service responsável por calcular métricas gerenciais e indicadores financeiros.
 * Separado do FinancialProjectionService para manter responsabilidades únicas.
 */
class ProjectionMetricsService
{
    /**
     * Calcula o Índice de Liquidez Projetada.
     * Fórmula: Contas a Receber / Total a Pagar
     * Interpretação: > 1.2 (saudável), 1.0-1.2 (atenção), < 1.0 (risco)
     *
     * @param  float  $receivable  Total de contas a receber
     * @param  float  $totalPayable  Total de contas a pagar
     * @return float Índice de liquidez
     */
    public function calculateLiquidityIndex(float $receivable, float $totalPayable): float
    {
        if ($totalPayable <= 0) {
            return 0.0;
        }

        return $receivable / $totalPayable;
    }

    /**
     * Calcula a Margem Operacional Projetada (%).
     * Fórmula: (Fluxo de Caixa / Contas a Receber) * 100
     * Interpretação: Percentual do recebível que restará após todas as obrigações
     *
     * @param  float  $cashFlow  Fluxo de caixa projetado
     * @param  float  $receivable  Total de contas a receber
     * @return float Margem operacional em percentual
     */
    public function calculateOperationalMargin(float $cashFlow, float $receivable): float
    {
        if ($receivable <= 0) {
            return 0.0;
        }

        return ($cashFlow / $receivable) * 100;
    }

    /**
     * Calcula o Grau de Comprometimento (%).
     * Fórmula: (Total a Pagar / Contas a Receber) * 100
     * Interpretação: Percentual do recebível comprometido com obrigações
     *
     * @param  float  $totalPayable  Total de contas a pagar
     * @param  float  $receivable  Total de contas a receber
     * @return float Grau de comprometimento em percentual
     */
    public function calculateCommitmentRate(float $totalPayable, float $receivable): float
    {
        if ($receivable <= 0) {
            return 0.0;
        }

        return ($totalPayable / $receivable) * 100;
    }

    /**
     * Avalia o nível de risco financeiro baseado em métricas.
     * Retorna: 'low', 'medium' ou 'high'
     *
     * Critérios:
     * - ALTO: Liquidez < 1.0 OU Fluxo Negativo > 20% do Recebível
     * - MÉDIO: Liquidez entre 1.0 e 1.2 OU Fluxo Negativo <= 20% do Recebível
     * - BAIXO: Liquidez >= 1.2 E Fluxo Positivo
     *
     * @param  float  $liquidityIndex  Índice de liquidez
     * @param  float  $cashFlow  Fluxo de caixa projetado
     * @param  float  $receivable  Total de contas a receber
     * @return string Nível de risco: 'low', 'medium', 'high'
     */
    public function assessRiskLevel(float $liquidityIndex, float $cashFlow, float $receivable): string
    {
        // Risco Alto
        if ($liquidityIndex < 1.0 || ($cashFlow < 0 && abs($cashFlow) > ($receivable * 0.2))) {
            return 'high';
        }

        // Risco Médio
        if ($liquidityIndex < 1.2 || ($cashFlow < 0 && abs($cashFlow) <= ($receivable * 0.2))) {
            return 'medium';
        }

        // Risco Baixo
        return 'low';
    }

    /**
     * Calcula análise de inadimplência agrupada por período de atraso.
     *
     * @param  Collection  $overduePayments  Coleção de pagamentos vencidos
     */
    public function calculateOverdueAnalysis(Collection $overduePayments): array
    {
        $today = Carbon::today();
        $totalOverdue = $overduePayments->sum('due_value_brl');
        $overdueCount = $overduePayments->count();

        // Agrupar por tempo de atraso
        $overdueByPeriod = [
            '0-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0,
        ];

        foreach ($overduePayments as $payment) {
            $daysOverdue = $today->diffInDays($payment->due_date);

            if ($daysOverdue <= 30) {
                $overdueByPeriod['0-30'] += (float) $payment->due_value_brl;
            } elseif ($daysOverdue <= 60) {
                $overdueByPeriod['31-60'] += (float) $payment->due_value_brl;
            } elseif ($daysOverdue <= 90) {
                $overdueByPeriod['61-90'] += (float) $payment->due_value_brl;
            } else {
                $overdueByPeriod['90+'] += (float) $payment->due_value_brl;
            }
        }

        return [
            'total_overdue' => $totalOverdue,
            'overdue_count' => $overdueCount,
            'overdue_by_period' => $overdueByPeriod,
            'overdue_payments' => $overduePayments,
        ];
    }

    /**
     * Calcula análise de eventos futuros no período.
     *
     * @param  Collection  $futureGigs  Coleção de gigs futuras
     * @param  GigFinancialCalculatorService  $calculator  Service de cálculos financeiros
     */
    public function calculateFutureEventsAnalysis(Collection $futureGigs, GigFinancialCalculatorService $calculator): array
    {
        $totalEvents = $futureGigs->count();
        $totalProjectedRevenue = 0;
        $totalProjectedCosts = 0;

        foreach ($futureGigs as $gig) {
            $totalProjectedRevenue += $calculator->calculateGrossCashBrl($gig);
            $totalProjectedCosts += $gig->gigCosts->sum('value_brl');
        }

        $projectedNetRevenue = $totalProjectedRevenue - $totalProjectedCosts;
        $averageRevenuePerEvent = $totalEvents > 0 ? ($totalProjectedRevenue / $totalEvents) : 0;

        return [
            'total_events' => $totalEvents,
            'total_projected_revenue' => $totalProjectedRevenue,
            'total_projected_costs' => $totalProjectedCosts,
            'projected_net_revenue' => $projectedNetRevenue,
            'average_revenue_per_event' => $averageRevenuePerEvent,
            'events' => $futureGigs,
        ];
    }

    /**
     * Calcula comparação com período anterior do mesmo tamanho.
     *
     * @param  Carbon  $startDate  Data inicial do período atual
     * @param  Carbon  $endDate  Data final do período atual
     * @param  float  $currentReceivable  Contas a receber do período atual
     * @param  float  $currentPayable  Contas a pagar do período atual
     * @param  float  $currentCashFlow  Fluxo de caixa do período atual
     * @param  callable  $fetchPreviousMetrics  Função para buscar métricas do período anterior
     */
    public function calculateComparativeAnalysis(
        Carbon $startDate,
        Carbon $endDate,
        float $currentReceivable,
        float $currentPayable,
        float $currentCashFlow,
        callable $fetchPreviousMetrics
    ): array {
        $periodDays = $startDate->diffInDays($endDate);

        // Período anterior tem o mesmo tamanho
        $previousStart = $startDate->copy()->subDays($periodDays + 1);
        $previousEnd = $startDate->copy()->subDay();

        // Busca métricas do período anterior via callback
        $previousMetrics = $fetchPreviousMetrics($previousStart, $previousEnd);

        // Calcula variações percentuais
        $receivableVariation = $previousMetrics['receivable'] > 0
            ? ((($currentReceivable - $previousMetrics['receivable']) / $previousMetrics['receivable']) * 100)
            : 0;

        $payableVariation = $previousMetrics['payable'] > 0
            ? ((($currentPayable - $previousMetrics['payable']) / $previousMetrics['payable']) * 100)
            : 0;

        $cashFlowVariation = $previousMetrics['cash_flow'] != 0
            ? ((($currentCashFlow - $previousMetrics['cash_flow']) / abs($previousMetrics['cash_flow'])) * 100)
            : 0;

        return [
            'current' => [
                'receivable' => $currentReceivable,
                'payable' => $currentPayable,
                'cash_flow' => $currentCashFlow,
            ],
            'previous' => [
                'receivable' => $previousMetrics['receivable'],
                'payable' => $previousMetrics['payable'],
                'cash_flow' => $previousMetrics['cash_flow'],
            ],
            'variations' => [
                'receivable' => $receivableVariation,
                'payable' => $payableVariation,
                'cash_flow' => $cashFlowVariation,
            ],
        ];
    }

    /**
     * Monta sumário executivo consolidado com todas as métricas.
     *
     * @param  float  $receivable  Total de contas a receber
     * @param  float  $payableArtists  Total a pagar para artistas
     * @param  float  $payableBookers  Total a pagar para bookers
     * @param  float  $payableExpenses  Total de despesas previstas
     * @param  float  $cashFlow  Fluxo de caixa projetado
     */
    public function buildExecutiveSummary(
        float $receivable,
        float $payableArtists,
        float $payableBookers,
        float $payableExpenses,
        float $cashFlow
    ): array {
        $totalPayable = $payableArtists + $payableBookers + $payableExpenses;

        $liquidityIndex = $this->calculateLiquidityIndex($receivable, $totalPayable);
        $operationalMargin = $this->calculateOperationalMargin($cashFlow, $receivable);
        $commitmentRate = $this->calculateCommitmentRate($totalPayable, $receivable);
        $riskLevel = $this->assessRiskLevel($liquidityIndex, $cashFlow, $receivable);

        return [
            'receivable' => $receivable,
            'total_payable' => $totalPayable,
            'cash_flow' => $cashFlow,
            'liquidity_index' => $liquidityIndex,
            'operational_margin' => $operationalMargin,
            'commitment_rate' => $commitmentRate,
            'risk_level' => $riskLevel,
            'breakdown' => [
                'payable_artists' => $payableArtists,
                'payable_bookers' => $payableBookers,
                'payable_expenses' => $payableExpenses,
            ],
        ];
    }
}
