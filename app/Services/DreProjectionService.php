<?php

namespace App\Services;

use App\Enums\AgencyCostType;
use App\Models\AgencyFixedCost;
use App\Models\Gig;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * DRE Projetada Service (Demonstração do Resultado do Exercício).
 * Calcula projeções financeiras no REGIME DE COMPETÊNCIA.
 *
 * Refatorado para usar o GigFinancialCalculatorService como fonte da verdade.
 */
class DreProjectionService
{
    protected Carbon $startDate;

    protected Carbon $endDate;

    protected GigFinancialCalculatorService $gigCalculator;

    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
        // Default: próximos 30 dias
        $this->startDate = Carbon::today()->startOfDay();
        $this->endDate = Carbon::today()->addDays(29)->endOfDay();
    }

    /**
     * Define o período de projeção.
     */
    public function setPeriod(Carbon $startDate, Carbon $endDate): self
    {
        $this->startDate = $startDate->copy()->startOfDay();
        $this->endDate = $endDate->copy()->endOfDay();

        return $this;
    }

    /**
     * Calcula a Receita Líquida Real da Agência (RLRA) usando o serviço central.
     * Esta é a MARGEM DE CONTRIBUIÇÃO do evento.
     *
     * @return float RLRA em BRL
     */
    public function calculateReceitaLiquidaRealAgencia(Gig $gig): float
    {
        return $this->gigCalculator->calculateAgencyNetCommissionBrl($gig);
    }

    /**
     * Retorna métricas consolidadas de um evento (para DRE), usando o calculador central.
     *
     * @return array Métricas do evento
     */
    public function getEventMetrics(Gig $gig): array
    {
        $rlra = $this->calculateReceitaLiquidaRealAgencia($gig);
        // Para consistência e análise, ainda podemos querer o "Cachê Líquido" da perspectiva da DRE.
        $grossCashBrl = $this->gigCalculator->calculateGrossCashBrl($gig);

        return [
            'gig_id' => $gig->id,
            'gig_date' => $gig->gig_date->format('Y-m-d'),
            'artist_name' => $gig->artist->name ?? 'N/A',
            'booker_name' => $gig->booker->name ?? 'N/A',
            'contract_value_brl' => $gig->cache_value_brl,
            'variable_costs_brl' => $this->gigCalculator->calculateTotalConfirmedExpensesBrl($gig),
            'cachee_liquido' => $grossCashBrl, // O antigo "Cachê Líquido"
            'receita_bruta_agencia' => $this->gigCalculator->calculateAgencyGrossCommissionBrl($gig),
            'custo_booker' => $this->gigCalculator->calculateBookerCommissionBrl($gig),
            'receita_liquida_real_agencia' => $rlra,
            'margin_percentage' => $grossCashBrl > 0 ? ($rlra / $grossCashBrl) * 100 : 0,
        ];
    }

    /**
     * Obtém eventos do período agrupados por mês (Regime de Competência).
     * Base: gig_date (data de execução do evento)
     *
     * IMPORTANTE: Gig usa SoftDeletes - query() já exclui registros deletados automaticamente.
     *
     * @return Collection Eventos agrupados por mês
     */
    public function getEventsGroupedByMonth(): Collection
    {
        $gigs = Gig::query()
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->with(['artist:id,name', 'booker:id,name', 'gigCosts'])
            ->orderBy('gig_date')
            ->get();

        return $gigs->groupBy(function ($gig) {
            return $gig->gig_date->format('Y-m');
        });
    }

    /**
     * Calcula DRE consolidada por mês.
     *
     * @return Collection DRE mensal
     */
    public function calculateMonthlyDre(): Collection
    {
        $gigsByMonth = $this->getEventsGroupedByMonth();

        return $gigsByMonth->map(function ($gigs, $yearMonth) {
            $totalRlra = 0;
            $totalRba = 0;
            $totalCbk = 0;
            $totalCl = 0;
            $eventCount = $gigs->count();

            $events = $gigs->map(function ($gig) use (&$totalRlra, &$totalRba, &$totalCbk, &$totalCl) {
                $metrics = $this->getEventMetrics($gig);

                $totalCl += $metrics['cachee_liquido'];
                $totalRba += $metrics['receita_bruta_agencia'];
                $totalCbk += $metrics['custo_booker'];
                $totalRlra += $metrics['receita_liquida_real_agencia'];

                return $metrics;
            });

            // Busca Custos Fixos do mês (segregados)
            $custoOperacional = $this->getOperationalCostsForMonth($yearMonth);
            $custoAdministrativo = $this->getAdministrativeCostsForMonth($yearMonth);
            $cfm = $custoOperacional + $custoAdministrativo;

            // Resultado Operacional = Total RLRA - CFM
            $resultadoOperacional = $totalRlra - $cfm;

            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->locale('pt_BR')->isoFormat('MMMM/YYYY'),
                'event_count' => $eventCount,
                'total_cachee_liquido' => $totalCl,
                'total_receita_bruta_agencia' => $totalRba,
                'total_custo_booker' => $totalCbk,
                'total_receita_liquida_real_agencia' => $totalRlra,
                'custo_operacional' => $custoOperacional,
                'custo_administrativo' => $custoAdministrativo,
                'custo_fixo_medio' => $cfm,
                'resultado_operacional' => $resultadoOperacional,
                'margin_percentage' => $totalCl > 0 ? ($totalRlra / $totalCl) * 100 : 0,
                'events' => $events->values(),
            ];
        })->sortKeys();
    }

    /**
     * Calcula DRE consolidada total do período.
     *
     * @return array DRE total
     */
    public function calculateTotalDre(): array
    {
        $monthlyDre = $this->calculateMonthlyDre();

        $totalRlra = $monthlyDre->sum('total_receita_liquida_real_agencia');
        $totalRba = $monthlyDre->sum('total_receita_bruta_agencia');
        $totalCbk = $monthlyDre->sum('total_custo_booker');
        $totalCl = $monthlyDre->sum('total_cachee_liquido');
        $totalCustoOperacional = $monthlyDre->sum('custo_operacional');
        $totalCustoAdministrativo = $monthlyDre->sum('custo_administrativo');
        $totalCfm = $monthlyDre->sum('custo_fixo_medio');
        $totalEventCount = $monthlyDre->sum('event_count');
        $resultadoOperacionalTotal = $totalRlra - $totalCfm;

        return [
            'period' => [
                'start' => $this->startDate->format('Y-m-d'),
                'end' => $this->endDate->format('Y-m-d'),
                'days' => $this->startDate->diffInDays($this->endDate) + 1,
            ],
            'totals' => [
                'event_count' => $totalEventCount,
                'total_cachee_liquido' => $totalCl,
                'total_receita_bruta_agencia' => $totalRba,
                'total_custo_booker' => $totalCbk,
                'total_receita_liquida_real_agencia' => $totalRlra,
                'total_custo_operacional' => $totalCustoOperacional,
                'total_custo_administrativo' => $totalCustoAdministrativo,
                'total_custo_fixo_medio' => $totalCfm,
                'resultado_operacional' => $resultadoOperacionalTotal,
                'margin_percentage' => $totalCl > 0 ? ($totalRlra / $totalCl) * 100 : 0,
            ],
            'monthly_breakdown' => $monthlyDre->values(),
        ];
    }

    /**
     * Obtém Custo Fixo Médio (CFM) de um mês específico.
     *
     * @param  string  $yearMonth  Formato: 'Y-m' (ex: '2025-10')
     * @param  string|null  $costType  Filtro por tipo: 'GIG' ou 'AGENCY' (null = todos)
     * @return float CFM em BRL
     */
    protected function getFixedCostsForMonth(string $yearMonth, ?string $costType = null): float
    {
        $query = AgencyFixedCost::active()->forMonth($yearMonth);

        if ($costType !== null) {
            $query->where('cost_type', $costType);
        }

        $total = $query->sum('monthly_value');

        return (float) $total;
    }

    /**
     * Obtém Custos Operacionais (operacional) de um mês específico.
     *
     * @param  string  $yearMonth  Formato: 'Y-m'
     * @return float Total em BRL
     */
    protected function getOperationalCostsForMonth(string $yearMonth): float
    {
        return $this->getFixedCostsForMonth($yearMonth, AgencyCostType::OPERACIONAL->value);
    }

    /**
     * Obtém Custos Administrativos (administrativo) de um mês específico.
     *
     * @param  string  $yearMonth  Formato: 'Y-m'
     * @return float Total em BRL
     */
    protected function getAdministrativeCostsForMonth(string $yearMonth): float
    {
        return $this->getFixedCostsForMonth($yearMonth, AgencyCostType::ADMINISTRATIVO->value);
    }

    /**
     * Calcula KPI: Ticket Médio (TM).
     * Fórmula: Soma de cache_value / Total de Gigs realizados
     *
     * IMPORTANTE: Gig usa SoftDeletes - whereBetween já exclui registros deletados.
     *
     * @return float Ticket médio em BRL
     */
    public function calculateTicketMedio(): float
    {
        $gigs = Gig::whereBetween('gig_date', [$this->startDate, $this->endDate])->get();

        $totalCount = $gigs->count();
        if ($totalCount === 0) {
            return 0.0;
        }

        $totalValue = $gigs->sum('cache_value_brl');

        return $totalValue / $totalCount;
    }

    /**
     * Calcula o Ponto de Equilíbrio em Valor (RLRA necessária).
     * É o CFM médio do período.
     *
     * @return float Ponto de equilíbrio em BRL
     */
    public function calculateBreakEvenPoint(): float
    {
        // Conta meses únicos no período (ano-mês)
        $monthlyDre = $this->calculateMonthlyDre();
        $months = max(1, $monthlyDre->count());

        $totalCfm = $monthlyDre->sum('custo_fixo_medio');

        return $totalCfm / $months;
    }

    /**
     * Retorna resumo executivo da DRE com KPIs principais.
     *
     * @return array Resumo executivo
     */
    public function getExecutiveSummary(): array
    {
        $dreTotal = $this->calculateTotalDre();
        $ticketMedio = $this->calculateTicketMedio();
        $breakEvenPoint = $this->calculateBreakEvenPoint();

        $resultadoOperacional = $dreTotal['totals']['resultado_operacional'];
        $totalRlra = $dreTotal['totals']['total_receita_liquida_real_agencia'];

        return [
            'periodo' => $dreTotal['period'],
            'kpis' => [
                'ticket_medio' => $ticketMedio,
                'ponto_equilibrio_mensal' => $breakEvenPoint,
                'total_eventos' => $dreTotal['totals']['event_count'],
                'margem_contribuicao_total' => $totalRlra,
                'resultado_operacional' => $resultadoOperacional,
                'margem_percentual' => $dreTotal['totals']['margin_percentage'],
                'status_financeiro' => $resultadoOperacional >= 0 ? 'lucrativo' : 'deficitario',
                'distancia_break_even' => $resultadoOperacional - $breakEvenPoint,
            ],
            'dre_mensal' => $dreTotal['monthly_breakdown'],
        ];
    }
}
