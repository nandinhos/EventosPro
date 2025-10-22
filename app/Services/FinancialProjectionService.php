<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service para calcular e agregar as projeções financeiras da agência.
 * Refatorado para usar ProjectionQueryBuilder e ProjectionMetricsService.
 */
class FinancialProjectionService
{
    /** @var Carbon A data de início do período de projeção. */
    protected $startDate;

    /** @var Carbon A data final do período de projeção. */
    protected $endDate;

    /** @var GigFinancialCalculatorService Instância do service de cálculo financeiro. */
    protected $calculatorService;

    /** @var ProjectionQueryBuilder Query builder otimizado. */
    protected $queryBuilder;

    /** @var ProjectionMetricsService Service de métricas. */
    protected $metricsService;

    public function __construct(
        GigFinancialCalculatorService $calculatorService,
        ProjectionQueryBuilder $queryBuilder,
        ProjectionMetricsService $metricsService
    ) {
        $this->calculatorService = $calculatorService;
        $this->queryBuilder = $queryBuilder;
        $this->metricsService = $metricsService;
        $this->setPeriod('30_days');
    }

    /**
     * Define o período da projeção.
     *
     * @param  string  $period  Identificador do período.
     * @param  string|null  $startDate  Data de início customizada (formato Y-m-d).
     * @param  string|null  $endDate  Data final customizada (formato Y-m-d).
     */
    public function setPeriod(string $period, ?string $startDate = null, ?string $endDate = null): void
    {
        $today = Carbon::today()->startOfDay();

        // Se datas customizadas foram fornecidas, usa elas
        if ($period === 'custom' && $startDate && $endDate) {
            $this->startDate = Carbon::parse($startDate)->startOfDay();
            $this->endDate = Carbon::parse($endDate)->endOfDay();

            return;
        }

        // Se apenas uma data foi fornecida (sem período predefinido)
        if (! $period && ($startDate || $endDate)) {
            $this->startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : $today;
            $this->endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : $today->copy()->addYear()->endOfDay();

            return;
        }

        // Períodos predefinidos
        $this->startDate = $today;

        switch ($period) {
            case '60_days':
                $this->endDate = $today->copy()->addDays(59)->endOfDay();
                break;
            case '90_days':
                $this->endDate = $today->copy()->addDays(89)->endOfDay();
                break;
            case 'next_semester':
                $endOfCurrentSemester = $today->month <= 6 ? $today->copy()->month(6)->endOfMonth() : $today->copy()->month(12)->endOfMonth();
                $this->endDate = $endOfCurrentSemester->copy()->addMonths(6)->endOfMonth()->endOfDay();
                break;
            case 'next_year':
                $this->endDate = $today->copy()->addYear()->endOfYear()->endOfDay();
                break;
            case '30_days':
            default:
                $this->endDate = $today->copy()->addDays(29)->endOfDay();
                break;
        }

    }

    /**
     * Calcula o total de contas a receber de clientes NO PERÍODO DE PROJEÇÃO.
     * Otimizado com query builder.
     *
     * @return float O valor total a receber em BRL.
     */
    public function getAccountsReceivable(): float
    {
        $payments = $this->queryBuilder->pendingPaymentsQuery($this->startDate, $this->endDate, true);
        $total = (float) $payments->sum('due_value_brl');

        return $total;
    }

    /**
     * Retorna a lista detalhada de pagamentos pendentes de clientes.
     */
    public function getUpcomingClientPayments(): Collection
    {
        return $this->queryBuilder->pendingPaymentsQuery($this->startDate, $this->endDate, true);
    }

    /**
     * Calcula as contas a pagar para Artistas no período de projeção.
     * Otimizado com query builder.
     */
    public function getAccountsPayableArtists(): float
    {
        $gigs = $this->queryBuilder->pendingGigsQuery($this->startDate, $this->endDate, 'artists', true);

        // Otimizado: calcular total sem logs individuais para evitar overhead
        $total = $gigs->sum(function ($gig) {
            return $this->calculatorService->calculateArtistInvoiceValueBrl($gig);
        });

        return (float) max(0, $total);
    }

    /**
     * Calcula as contas a pagar para Bookers (comissões) no período.
     * Otimizado com query builder.
     */
    public function getAccountsPayableBookers(): float
    {
        $gigs = $this->queryBuilder->pendingGigsQuery($this->startDate, $this->endDate, 'bookers', true);

        // Otimizado: calcular total sem logs individuais para evitar overhead
        $total = $gigs->sum(function ($gig) {
            $commission = $this->calculatorService->calculateBookerCommissionBrl($gig);

            return $commission > 0 ? $commission : 0;
        });

        return (float) max(0, $total);
    }

    /**
     * Calcula as contas a pagar (Despesas Previstas).
     * Otimizado com query builder.
     */
    public function getAccountsPayableExpenses(): float
    {
        $costs = $this->queryBuilder->pendingExpensesQuery($this->endDate, true);
        $totalExpenses = (float) $costs->sum('value_brl');

        // //Log::info("[FinancialProjectionService] Total Contas a Pagar Despesas: {$totalExpenses}. Quantidade de custos: ".$costs->count());

        return $totalExpenses;
    }

    /**
     * Obtém as despesas previstas agrupadas por centro de custo para o período.
     */
    public function getProjectedExpensesByCostCenter(): Collection
    {
        $costs = $this->queryBuilder->pendingExpensesQuery($this->endDate, true);

        return $costs->groupBy('cost_center_id')
            ->map(function ($group, $costCenterId) {
                $firstCost = $group->first();

                $groupName = 'Sem Centro de Custo';
                if ($firstCost && $firstCost->costCenter) {
                    $groupName = __('cost_centers.'.$firstCost->costCenter->name);
                }

                $totalBrl = $group->sum('value_brl');
                $expenses = $group->map(function ($cost) {
                    $orderByDate = null;
                    $isGigDateFallback = false;

                    if ($cost->expense_date) {
                        $orderByDate = Carbon::parse($cost->expense_date);
                    } elseif ($cost->gig && $cost->gig->gig_date) {
                        $orderByDate = Carbon::parse($cost->gig->gig_date);
                        $isGigDateFallback = true;
                    }

                    return [
                        'gig_id' => $cost->gig_id,
                        'gig_contract_number' => $cost->gig->contract_number ?? 'Gig #'.$cost->gig_id,
                        'gig_artist_name' => $cost->gig->artist->name ?? 'N/A',
                        'description' => $cost->description,
                        'expense_date_formatted' => $orderByDate ? $orderByDate->format('d/m/Y').($isGigDateFallback ? ' (Gig)' : '') : 'N/A',
                        'value_brl' => (float) $cost->value_brl,
                        'currency' => strtoupper($cost->currency ?? 'BRL'),
                        '_order_by_date_object' => $orderByDate,
                    ];
                })->sortBy(function ($expense) {
                    return $expense['_order_by_date_object'] ? $expense['_order_by_date_object']->timestamp : PHP_INT_MAX;
                });

                return [
                    'cost_center_name' => $groupName,
                    'total_brl' => $totalBrl,
                    'expenses' => $expenses->values(),
                ];
            })->sortBy('cost_center_name')->values();
    }

    /**
     * Fluxo de Caixa Projetado.
     */
    public function getProjectedCashFlow(): float
    {
        $receivable = $this->getAccountsReceivable();
        $payableArtists = $this->getAccountsPayableArtists();
        $payableBookers = $this->getAccountsPayableBookers();
        $payableExpenses = $this->getAccountsPayableExpenses();

        $totalPayable = $payableArtists + $payableBookers + $payableExpenses;
        $cashFlow = $receivable - $totalPayable;

        // //Log::info("[FinancialProjectionService] Fluxo de Caixa Projetado: Recebível {$receivable} - Total a Pagar {$totalPayable} = {$cashFlow}");

        return (float) $cashFlow;
    }

    /**
     * Retorna a lista de Gigs com pagamentos pendentes a artistas ou bookers.
     *
     * @param  string  $type  'artists' ou 'bookers'
     */
    public function getUpcomingInternalPayments(string $type): Collection
    {
        return $this->queryBuilder->pendingGigsQuery($this->startDate, $this->endDate, $type, true);
    }

    /**
     * Calcula métricas consolidadas para a diretoria.
     * DELEGADO para ProjectionMetricsService.
     */
    public function getExecutiveSummary(): array
    {
        $receivable = $this->getAccountsReceivable();
        $payableArtists = $this->getAccountsPayableArtists();
        $payableBookers = $this->getAccountsPayableBookers();
        $payableExpenses = $this->getAccountsPayableExpenses();
        $cashFlow = $this->getProjectedCashFlow();

        return $this->metricsService->buildExecutiveSummary(
            $receivable,
            $payableArtists,
            $payableBookers,
            $payableExpenses,
            $cashFlow
        );
    }

    /**
     * Retorna análise detalhada de pagamentos vencidos.
     * DELEGADO para ProjectionMetricsService.
     */
    public function getOverdueAnalysis(): array
    {
        $overduePayments = $this->queryBuilder->overduePaymentsQuery(true);

        return $this->metricsService->calculateOverdueAnalysis($overduePayments);
    }

    /**
     * Retorna análise de eventos futuros no período.
     * DELEGADO para ProjectionMetricsService.
     */
    public function getFutureEventsAnalysis(): array
    {
        $futureGigs = $this->queryBuilder->futureEventsQuery($this->startDate, $this->endDate, true);

        return $this->metricsService->calculateFutureEventsAnalysis($futureGigs, $this->calculatorService);
    }

    /**
     * Retorna comparação com período anterior (mesmo tamanho).
     * DELEGADO para ProjectionMetricsService.
     */
    public function getComparativePeriodAnalysis(): array
    {
        $currentReceivable = $this->getAccountsReceivable();
        $currentPayable = $this->getAccountsPayableArtists() + $this->getAccountsPayableBookers() + $this->getAccountsPayableExpenses();
        $currentCashFlow = $this->getProjectedCashFlow();

        // Callback para buscar métricas do período anterior
        $fetchPreviousMetrics = function (Carbon $previousStart, Carbon $previousEnd) {
            // Temporariamente salva as datas atuais
            $currentStart = $this->startDate;
            $currentEnd = $this->endDate;

            // Define período anterior
            $this->startDate = $previousStart;
            $this->endDate = $previousEnd;

            $previousReceivable = $this->getAccountsReceivable();
            $previousPayable = $this->getAccountsPayableArtists() + $this->getAccountsPayableBookers() + $this->getAccountsPayableExpenses();
            $previousCashFlow = $this->getProjectedCashFlow();

            // Restaura período atual
            $this->startDate = $currentStart;
            $this->endDate = $currentEnd;

            return [
                'receivable' => $previousReceivable,
                'payable' => $previousPayable,
                'cash_flow' => $previousCashFlow,
            ];
        };

        return $this->metricsService->calculateComparativeAnalysis(
            $this->startDate,
            $this->endDate,
            $currentReceivable,
            $currentPayable,
            $currentCashFlow,
            $fetchPreviousMetrics
        );
    }

    /**
     * Obtém métricas globais com cache.
     * Cache de 5 minutos para performance.
     */
    public function getGlobalMetrics(): array
    {
        return Cache::tags(['projections', 'global'])->remember('global_metrics', 300, function () {
            // Define período global
            $this->setPeriod('', Carbon::create(2000, 1, 1)->format('Y-m-d'), Carbon::create(2100, 12, 31)->format('Y-m-d'));

            $receivable = $this->getAccountsReceivable();
            $payableArtists = $this->getAccountsPayableArtists();
            $payableBookers = $this->getAccountsPayableBookers();
            $payableExpenses = $this->getAccountsPayableExpenses();
            $cashFlow = $this->getProjectedCashFlow();
            $overdueAnalysis = $this->getOverdueAnalysis();

            $totalPayable = $payableArtists + $payableBookers + $payableExpenses;
            $liquidityIndex = $this->metricsService->calculateLiquidityIndex($receivable, $totalPayable);
            $operationalMargin = $this->metricsService->calculateOperationalMargin($cashFlow, $receivable);
            $commitmentRate = $this->metricsService->calculateCommitmentRate($totalPayable, $receivable);
            $riskLevel = $this->metricsService->assessRiskLevel($liquidityIndex, $cashFlow, $receivable);

            return [
                'total_receivable' => $receivable,
                'total_payable_artists' => $payableArtists,
                'total_payable_bookers' => $payableBookers,
                'total_payable_expenses' => $payableExpenses,
                'total_cash_flow' => $cashFlow,
                'overdue_analysis' => $overdueAnalysis,
                'liquidity_index' => $liquidityIndex,
                'operational_margin' => $operationalMargin,
                'commitment_rate' => $commitmentRate,
                'risk_level' => $riskLevel,
            ];
        });
    }

    /**
     * Invalida o cache de projeções.
     * Deve ser chamado ao confirmar pagamentos/despesas.
     */
    public function clearCache(): void
    {
        Cache::tags(['projections'])->flush();
        // //Log::info('[FinancialProjectionService] Cache de projeções invalidado.');
    }

    /**
     * Método de compatibilidade backwards - DEPRECATED.
     * Use getUpcomingClientPayments() ou getUpcomingInternalPayments() diretamente.
     *
     * @deprecated Use getUpcomingClientPayments() para clientes ou getUpcomingInternalPayments($type) para artistas/bookers
     */
    public function getUpcomingPayments(string $type)
    {
        switch ($type) {
            case 'clients':
                return $this->getUpcomingClientPayments();
            case 'artists':
            case 'bookers':
                return $this->getUpcomingInternalPayments($type);
            default:
                return collect([]);
        }
    }
}
