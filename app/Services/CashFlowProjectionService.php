<?php

namespace App\Services;

use App\Models\Gig;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cash Flow Projection Service (Fluxo de Caixa Projetado).
 * Calcula projeções financeiras no REGIME DE CAIXA.
 *
 * Baseado em AGENT_PROJECTION.md:
 * - Entradas (Recebimento): payments.received_value_actual com payments.received_date_actual
 * - Saídas (Artistas/Booker): Baseadas na data de execução (gig_date) - Regime de Competência
 *
 * Diferença entre DRE e Fluxo de Caixa:
 * - DRE: Competência (quando ocorre o evento)
 * - Fluxo de Caixa: Entrada = quando recebe / Saída = quando executa evento
 */
class CashFlowProjectionService
{
    protected Carbon $startDate;

    protected Carbon $endDate;

    protected DreProjectionService $dreService;

    protected GigFinancialCalculatorService $gigCalculator;

    public function __construct(DreProjectionService $dreService, GigFinancialCalculatorService $gigCalculator)
    {
        $this->dreService = $dreService;
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

        $this->dreService->setPeriod($startDate, $endDate);

        return $this;
    }

    /**
     * Calcula ENTRADAS (Recebimentos de Clientes) por mês.
     * Base: payments.received_date_actual (Regime de Caixa)
     *
     * @return Collection Entradas agrupadas por mês
     */
    public function calculateMonthlyInflows(): Collection
    {
        $payments = Payment::query()
            ->whereNotNull('received_date_actual')
            ->whereBetween('received_date_actual', [$this->startDate, $this->endDate])
            ->whereHas('gig') // Garante que apenas payments com gigs não-deletados sejam incluídos
            ->with('gig:id,contract_number,artist_id', 'gig.artist:id,name')
            ->orderBy('received_date_actual')
            ->get();

        return $payments->groupBy(function ($payment) {
            return Carbon::parse($payment->received_date_actual)->format('Y-m');
        })->map(function ($paymentsInMonth, $yearMonth) {
            $totalInflow = $paymentsInMonth->sum('received_value_actual_brl');

            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->locale('pt_BR')->isoFormat('MMMM/YYYY'),
                'total_inflow' => $totalInflow,
                'payment_count' => $paymentsInMonth->count(),
                'payments' => $paymentsInMonth->map(function ($payment) {
                    return [
                        'payment_id' => $payment->id,
                        'gig_id' => $payment->gig_id,
                        'gig_contract' => $payment->gig->contract_number ?? 'N/A',
                        'artist_name' => $payment->gig->artist->name ?? 'N/A',
                        'received_date' => Carbon::parse($payment->received_date_actual)->format('d/m/Y'),
                        'received_value_brl' => $payment->received_value_actual_brl,
                        'currency' => $payment->currency,
                    ];
                })->values(),
            ];
        })->sortKeys();
    }

    /**
     * Calcula SAÍDAS (Pagamentos a Artistas/Bookers) por mês.
     * Base: gig_date (Regime de Competência para Saídas)
     *
     * @return Collection Saídas agrupadas por mês
     */
    public function calculateMonthlyOutflows(): Collection
    {
        $gigs = Gig::query()
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->with(['artist:id,name', 'booker:id,name', 'gigCosts'])
            ->orderBy('gig_date')
            ->get();

        return $gigs->groupBy(function ($gig) {
            return $gig->gig_date->format('Y-m');
        })->map(function ($gigsInMonth, $yearMonth) {
            $totalArtistPayout = 0;
            $totalBookerCommission = 0;

            $outflows = $gigsInMonth->map(function ($gig) use (&$totalArtistPayout, &$totalBookerCommission) {
                // Utiliza o calculador central para obter os valores corretos
                $artistPayout = $this->gigCalculator->calculateArtistInvoiceValueBrl($gig);
                $bookerCommission = $this->gigCalculator->calculateBookerCommissionBrl($gig);

                $totalArtistPayout += $artistPayout;
                $totalBookerCommission += $bookerCommission;

                return [
                    'gig_id' => $gig->id,
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'artist_name' => $gig->artist->name ?? 'N/A',
                    'booker_name' => $gig->booker->name ?? 'N/A',
                    'contract_value_brl' => $gig->cache_value_brl,
                    'cachee_liquido' => $this->gigCalculator->calculateGrossCashBrl($gig), // O "Cachê Líquido" da DRE é o "GrossCashBrl" do calculator
                    'artist_payout' => $artistPayout,
                    'booker_commission' => $bookerCommission,
                    'total_outflow_event' => $artistPayout + $bookerCommission,
                ];
            });

            $totalOutflow = $totalArtistPayout + $totalBookerCommission;

            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->locale('pt_BR')->isoFormat('MMMM/YYYY'),
                'total_artist_payout' => $totalArtistPayout,
                'total_booker_commission' => $totalBookerCommission,
                'total_outflow' => $totalOutflow,
                'event_count' => $gigsInMonth->count(),
                'outflows' => $outflows->values(),
            ];
        })->sortKeys();
    }

    /**
     * Calcula Fluxo de Caixa consolidado por mês.
     * Fluxo = Entradas - Saídas
     *
     * @return Collection Fluxo de caixa mensal
     */
    public function calculateMonthlyCashFlow(): Collection
    {
        $inflows = $this->calculateMonthlyInflows();
        $outflows = $this->calculateMonthlyOutflows();

        // Mescla inflows e outflows por mês
        $allMonths = $inflows->keys()->merge($outflows->keys())->unique()->sort();

        return $allMonths->map(function ($yearMonth) use ($inflows, $outflows) {
            $inflowData = $inflows->get($yearMonth, ['total_inflow' => 0]);
            $outflowData = $outflows->get($yearMonth, [
                'total_artist_payout' => 0,
                'total_booker_commission' => 0,
                'total_outflow' => 0,
            ]);

            $totalInflow = $inflowData['total_inflow'] ?? 0;
            $totalOutflow = $outflowData['total_outflow'] ?? 0;
            $netCashFlow = $totalInflow - $totalOutflow;

            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->locale('pt_BR')->isoFormat('MMMM/YYYY'),
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'net_cash_flow' => $netCashFlow,
                'inflow_details' => $inflowData,
                'outflow_details' => $outflowData,
            ];
        })->values();
    }

    /**
     * Calcula Fluxo de Caixa consolidado total do período.
     *
     * @return array Fluxo de caixa total
     */
    public function calculateTotalCashFlow(): array
    {
        $monthlyCashFlow = $this->calculateMonthlyCashFlow();

        $totalInflow = $monthlyCashFlow->sum('total_inflow');
        $totalOutflow = $monthlyCashFlow->sum('total_outflow');
        $netCashFlow = $totalInflow - $totalOutflow;

        return [
            'period' => [
                'start' => $this->startDate->format('Y-m-d'),
                'end' => $this->endDate->format('Y-m-d'),
                'days' => $this->startDate->diffInDays($this->endDate) + 1,
            ],
            'totals' => [
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'net_cash_flow' => $netCashFlow,
                'cash_flow_margin' => $totalInflow > 0 ? ($netCashFlow / $totalInflow) * 100 : 0,
            ],
            'monthly_breakdown' => $monthlyCashFlow,
        ];
    }

    /**
     * Retorna resumo executivo do Fluxo de Caixa com análise de liquidez.
     *
     * @return array Resumo executivo
     */
    public function getExecutiveSummary(): array
    {
        $cashFlowTotal = $this->calculateTotalCashFlow();

        $netCashFlow = $cashFlowTotal['totals']['net_cash_flow'];
        $totalInflow = $cashFlowTotal['totals']['total_inflow'];
        $totalOutflow = $cashFlowTotal['totals']['total_outflow'];

        // Índice de Liquidez Projetada: Entradas / Saídas
        $liquidityIndex = $totalOutflow > 0 ? $totalInflow / $totalOutflow : 0;

        // Avaliação de Risco
        $riskLevel = 'low';
        if ($liquidityIndex < 1.0) {
            $riskLevel = 'high';
        } elseif ($liquidityIndex < 1.2) {
            $riskLevel = 'medium';
        }

        return [
            'periodo' => $cashFlowTotal['period'],
            'kpis' => [
                'total_entradas' => $totalInflow,
                'total_saidas' => $totalOutflow,
                'fluxo_caixa_liquido' => $netCashFlow,
                'margem_fluxo_caixa' => $cashFlowTotal['totals']['cash_flow_margin'],
                'indice_liquidez' => $liquidityIndex,
                'nivel_risco' => $riskLevel,
                'status_financeiro' => $netCashFlow >= 0 ? 'positivo' : 'negativo',
            ],
            'fluxo_mensal' => $cashFlowTotal['monthly_breakdown'],
        ];
    }

    /**
     * Calcula projeção de Contas a Receber (Pendentes).
     * Pagamentos com due_date no período mas confirmed_at = null.
     *
     * @return array Contas a receber
     */
    public function calculateAccountsReceivable(): array
    {
        $pendingPayments = Payment::query()
            ->whereNull('confirmed_at')
            ->whereBetween('due_date', [$this->startDate, $this->endDate])
            ->whereHas('gig') // Garante que apenas payments com gigs não-deletados sejam incluídos
            ->with('gig:id,contract_number,artist_id', 'gig.artist:id,name')
            ->orderBy('due_date')
            ->get();

        $totalReceivable = $pendingPayments->sum('due_value_brl');

        $paymentsByMonth = $pendingPayments->groupBy(function ($payment) {
            return Carbon::parse($payment->due_date)->format('Y-m');
        })->map(function ($payments, $yearMonth) {
            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->locale('pt_BR')->isoFormat('MMMM/YYYY'),
                'total' => $payments->sum('due_value_brl'),
                'count' => $payments->count(),
            ];
        })->sortKeys();

        return [
            'total_receivable' => $totalReceivable,
            'payment_count' => $pendingPayments->count(),
            'by_month' => $paymentsByMonth->values(),
            'payments' => $pendingPayments->map(function ($payment) {
                return [
                    'payment_id' => $payment->id,
                    'gig_id' => $payment->gig_id,
                    'gig_contract' => $payment->gig->contract_number ?? 'N/A',
                    'artist_name' => $payment->gig->artist->name ?? 'N/A',
                    'due_date' => Carbon::parse($payment->due_date)->format('d/m/Y'),
                    'due_value_brl' => $payment->due_value_brl,
                    'days_until_due' => Carbon::today()->diffInDays(Carbon::parse($payment->due_date), false),
                ];
            })->values(),
        ];
    }

    /**
     * Compara DRE (Competência) vs Fluxo de Caixa (Caixa).
     *
     * @return array Análise comparativa
     */
    public function compareWithDre(): array
    {
        $dreTotal = $this->dreService->calculateTotalDre();
        $cashFlowTotal = $this->calculateTotalCashFlow();

        $dreResultado = $dreTotal['totals']['resultado_operacional'];
        $cashFlowLiquido = $cashFlowTotal['totals']['net_cash_flow'];

        $difference = $cashFlowLiquido - $dreResultado;

        return [
            'dre_resultado_operacional' => $dreResultado,
            'cash_flow_liquido' => $cashFlowLiquido,
            'diferenca_regime' => $difference,
            'analise' => [
                'regime_competencia_rlra' => $dreTotal['totals']['total_receita_liquida_real_agencia'],
                'regime_caixa_entradas' => $cashFlowTotal['totals']['total_inflow'],
                'diferenca_entradas' => $cashFlowTotal['totals']['total_inflow'] - $dreTotal['totals']['total_receita_liquida_real_agencia'],
            ],
        ];
    }

    /**
     * Calcula detalhes dos pagamentos pendentes aos artistas.
     * Baseado em gigs sem settlements de artistas completos.
     *
     * @return array Detalhes de pagamentos pendentes aos artistas
     */
    public function calculateArtistPaymentDetails(): array
    {
        $gigs = Gig::query()
            ->where('gig_date', '<=', Carbon::today()) // Apenas eventos passados
            ->with(['artist:id,name', 'booker:id,name', 'settlements', 'gigCosts'])
            ->get();

        $pendingArtistPayments = [];
        $totalPending = 0;
        $totalGigs = 0;

        foreach ($gigs as $gig) {
            // Calcula o valor total da NF do artista usando o serviço central
            $artistPayout = $this->gigCalculator->calculateArtistInvoiceValueBrl($gig);

            // Verifica settlements existentes para o artista
            $artistSettlements = $gig->settlements->sum('artist_payment_value');

            $pendingAmount = $artistPayout - $artistSettlements;

            // Se há valor pendente
            if ($pendingAmount > 0.01) {
                $pendingArtistPayments[] = [
                    'gig_id' => $gig->id,
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'gig_contract' => $gig->contract_number ?? "Gig #{$gig->id}",
                    'artist_name' => $gig->artist->stage_name ?? $gig->artist->name ?? 'N/A',
                    'booker_name' => $gig->booker->name ?? 'N/A',
                    'cache_bruto_brl' => $gig->cache_value_brl,
                    'cachee_liquido' => $this->gigCalculator->calculateGrossCashBrl($gig), // Corrigido: usa o calculador
                    'artist_payout_total' => $artistPayout,
                    'amount_paid' => $artistSettlements,
                    'amount_pending' => $pendingAmount,
                    'days_since_event' => Carbon::today()->diffInDays($gig->gig_date),
                    'payment_status' => $artistSettlements > 0 ? 'partial' : 'unpaid',
                ];

                $totalPending += $pendingAmount;
                $totalGigs++;
            }
        }

        // Ordena por data do evento (mais antigos primeiro)
        usort($pendingArtistPayments, function ($a, $b) {
            return $b['days_since_event'] <=> $a['days_since_event'];
        });

        return [
            'total_pending' => $totalPending,
            'gig_count' => $totalGigs,
            'payments' => $pendingArtistPayments,
            'by_urgency' => [
                'critical' => collect($pendingArtistPayments)->where('days_since_event', '>', 60)->sum('amount_pending'),
                'high' => collect($pendingArtistPayments)->whereBetween('days_since_event', [30, 60])->sum('amount_pending'),
                'medium' => collect($pendingArtistPayments)->whereBetween('days_since_event', [15, 29])->sum('amount_pending'),
                'normal' => collect($pendingArtistPayments)->where('days_since_event', '<', 15)->sum('amount_pending'),
            ],
        ];
    }

    /**
     * Calcula detalhes das comissões pendentes aos bookers.
     * Baseado em gigs sem settlements de bookers completos.
     *
     * @return array Detalhes de comissões pendentes aos bookers
     */
    public function calculateBookerCommissionDetails(): array
    {
        $gigs = Gig::query()
            ->where('gig_date', '<=', Carbon::today()) // Apenas eventos passados
            ->with(['artist:id,name', 'booker:id,name', 'settlements', 'gigCosts'])
            ->get();

        $pendingBookerCommissions = [];
        $totalPending = 0;
        $totalGigs = 0;

        $bookerSummary = [];

        foreach ($gigs as $gig) {
            // Calcula a comissão do booker
            $bookerCommission = $this->gigCalculator->calculateBookerCommissionBrl($gig);

            // Verifica settlements existentes para o booker
            $bookerSettlements = $gig->settlements->sum('booker_commission_value_paid');

            $pendingAmount = $bookerCommission - $bookerSettlements;

            // Se há valor pendente
            if ($pendingAmount > 0.01) {
                $bookerId = $gig->booker_id;
                $bookerName = $gig->booker->name ?? 'N/A';

                $pendingBookerCommissions[] = [
                    'gig_id' => $gig->id,
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'gig_contract' => $gig->contract_number ?? "Gig #{$gig->id}",
                    'artist_name' => $gig->artist->stage_name ?? $gig->artist->name ?? 'N/A',
                    'booker_name' => $bookerName,
                    'booker_id' => $bookerId,
                    'cache_bruto_brl' => $gig->cache_value_brl,
                    'commission_rate' => $gig->booker_commission_percentage ?? 0,
                    'commission_total' => $bookerCommission,
                    'amount_paid' => $bookerSettlements,
                    'amount_pending' => $pendingAmount,
                    'days_since_event' => Carbon::today()->diffInDays($gig->gig_date),
                    'payment_status' => $bookerSettlements > 0 ? 'partial' : 'unpaid',
                ];

                // Acumula por booker
                if (! isset($bookerSummary[$bookerId])) {
                    $bookerSummary[$bookerId] = [
                        'booker_name' => $bookerName,
                        'total_pending' => 0,
                        'gig_count' => 0,
                    ];
                }
                $bookerSummary[$bookerId]['total_pending'] += $pendingAmount;
                $bookerSummary[$bookerId]['gig_count']++;

                $totalPending += $pendingAmount;
                $totalGigs++;
            }
        }

        // Ordena por data do evento (mais antigos primeiro)
        usort($pendingBookerCommissions, function ($a, $b) {
            return $b['days_since_event'] <=> $a['days_since_event'];
        });

        return [
            'total_pending' => $totalPending,
            'gig_count' => $totalGigs,
            'commissions' => $pendingBookerCommissions,
            'by_booker' => array_values($bookerSummary),
            'by_urgency' => [
                'critical' => collect($pendingBookerCommissions)->where('days_since_event', '>', 60)->sum('amount_pending'),
                'high' => collect($pendingBookerCommissions)->whereBetween('days_since_event', [30, 60])->sum('amount_pending'),
                'medium' => collect($pendingBookerCommissions)->whereBetween('days_since_event', [15, 29])->sum('amount_pending'),
                'normal' => collect($pendingBookerCommissions)->where('days_since_event', '<', 15)->sum('amount_pending'),
            ],
        ];
    }

    /**
     * Calcula detalhes das despesas previstas.
     * Baseado em custos fixos da agência (AgencyFixedCost).
     *
     * @return array Detalhes de despesas previstas
     */
    public function calculateProjectedExpenses(): array
    {
        // Busca custos fixos da agência
        $fixedCosts = \App\Models\AgencyFixedCost::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->get();

        $expensesByCategory = [];
        $totalMonthly = 0;

        foreach ($fixedCosts as $cost) {
            $category = $cost->category ?? 'Outros';

            if (! isset($expensesByCategory[$category])) {
                $expensesByCategory[$category] = [
                    'category' => $category,
                    'items' => [],
                    'total' => 0,
                ];
            }

            $expensesByCategory[$category]['items'][] = [
                'id' => $cost->id,
                'description' => $cost->description,
                'amount_monthly' => $cost->monthly_value,
                'payment_day' => $cost->payment_day ?? null,
            ];

            $expensesByCategory[$category]['total'] += $cost->monthly_value;
            $totalMonthly += $cost->monthly_value;
        }

        // Calcula projeção para o período definido
        $periodMonths = $this->startDate->diffInMonths($this->endDate) + 1;
        $totalProjected = $totalMonthly * $periodMonths;

        return [
            'total_monthly' => $totalMonthly,
            'total_projected' => $totalProjected,
            'period_months' => $periodMonths,
            'by_category' => array_values($expensesByCategory),
            'expense_count' => $fixedCosts->count(),
        ];
    }
}
