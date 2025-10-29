<?php

namespace App\Http\Controllers;

use App\Models\AgencyFixedCost;
use App\Models\Gig;
use App\Models\Payment;
use App\Models\Settlement;
use App\Services\CashFlowProjectionService;
use App\Services\DreProjectionService;
use App\Services\ProjectionCacheService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para Projeções Financeiras (DRE e Fluxo de Caixa).
 *
 * Implementa as especificações do AGENT_PROJECTION.md:
 * - DRE Projetada (Regime de Competência)
 * - Fluxo de Caixa Projetado (Regime de Caixa)
 * - KPIs: Ticket Médio, Ponto de Equilíbrio, Margem de Contribuição
 */
class FinancialProjectionController extends Controller
{
    protected DreProjectionService $dreService;

    protected CashFlowProjectionService $cashFlowService;

    protected ProjectionCacheService $cacheService;

    public function __construct(
        DreProjectionService $dreService,
        CashFlowProjectionService $cashFlowService,
        ProjectionCacheService $cacheService
    ) {
        $this->dreService = $dreService;
        $this->cashFlowService = $cashFlowService;
        $this->cacheService = $cacheService;
    }

    /**
     * Exibe o dashboard consolidado de projeções financeiras.
     */
    public function index(Request $request): View
    {
        // Validação de inputs
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'view_mode' => 'nullable|in:dre,cashflow,comparison',
            'show_global' => 'nullable|boolean',
        ]);

        // Verifica se deve mostrar métricas globais
        $showGlobal = $request->boolean('show_global');

        // Define período - só usa valores padrão se não for visualização global
        $startDate = null;
        $endDate = null;

        if (! $showGlobal) {
            $startDate = $request->input('start_date')
                ? Carbon::parse($request->input('start_date'))
                : null;

            $endDate = $request->input('end_date')
                ? Carbon::parse($request->input('end_date'))
                : null;
        }

        $viewMode = $request->input('view_mode', 'dre');

        // Carrega dados dependendo do modo de visualização
        $dreData = null;
        $cashFlowData = null;
        $comparisonData = null;
        $cashFlowSummary = null;
        $dreSummary = null;

        // Só configura serviços e carrega dados se não for visualização global
        if (! $showGlobal && $startDate && $endDate) {
            $this->dreService->setPeriod($startDate, $endDate);
            $this->cashFlowService->setPeriod($startDate, $endDate);

            if ($viewMode === 'dre' || $viewMode === 'comparison') {
                $dreData = $this->dreService->getExecutiveSummary();
            }

            if ($viewMode === 'cashflow' || $viewMode === 'comparison') {
                $cashFlowData = $this->cashFlowService->getExecutiveSummary();
            }

            if ($viewMode === 'comparison') {
                $comparisonData = $this->cashFlowService->compareWithDre();
            }

            // Monta estrutura compatível com a view antiga (global_metrics e period_metrics)
            // Usa os dados do Fluxo de Caixa como base para métricas globais
            $cashFlowSummary = $this->cashFlowService->getExecutiveSummary();
            $dreSummary = $this->dreService->getExecutiveSummary();
        } else {
            // Para visualização global, carrega dados sem restrição de período
            $this->dreService->setPeriod(Carbon::today()->subYear(), Carbon::today()->addYear());
            $this->cashFlowService->setPeriod(Carbon::today()->subYear(), Carbon::today()->addYear());

            $cashFlowSummary = $this->cashFlowService->getExecutiveSummary();
            $dreSummary = $this->dreService->getExecutiveSummary();
        }

        // Calcula contas a receber pendentes (para métricas globais, usa período amplo)
        $accountsReceivable = $this->calculateGlobalAccountsReceivable();
        $strategicBalance = $this->calculateStrategicBalance(); // Nova chamada

        // Calcula detalhes dos pagamentos pendentes
        $artistPaymentDetails = $this->cashFlowService->calculateArtistPaymentDetails();
        $bookerCommissionDetails = $this->cashFlowService->calculateBookerCommissionDetails();
        $projectedExpenses = $this->cashFlowService->calculateProjectedExpenses();
        $gigExpenses = $this->calculateTotalGigExpenses(); // Novo: despesas de eventos (GigCost)

        // Calcula Total a Pagar Consolidado (Artistas + Bookers + Despesas + Custos Operacionais projetados)
        // Usamos 3 meses de custos operacionais como projeção padrão para o total consolidado
        $projectedMonths = 3;
        $totalPayableConsolidated = $artistPaymentDetails['total_pending']
            + $bookerCommissionDetails['total_pending']
            + $gigExpenses['total_expenses']
            + ($projectedExpenses['total_monthly'] * $projectedMonths);

        $globalMetrics = [
            'total_receivable' => $accountsReceivable['total_receivable'], // Contas pendentes reais
            'total_receivable_past_events' => $accountsReceivable['total_overdue'], // Recebíveis de eventos passados
            'total_receivable_future_events' => $accountsReceivable['total_future'], // Recebíveis de eventos futuros
            'total_payable_artists' => $artistPaymentDetails['total_pending'], // Dados reais dos settlements
            'total_payable_bookers' => $bookerCommissionDetails['total_pending'], // Dados reais dos settlements
            'total_payable_expenses' => $gigExpenses['total_expenses'], // Despesas de eventos (GigCost)
            'total_expenses' => $gigExpenses, // Detalhes completos das despesas
            'operational_cost_count' => $projectedExpenses['expense_count'], // Contagem de custos operacionais
            'operational_cost_monthly' => $projectedExpenses['total_monthly'], // Total mensal de custos fixos
            'operational_cost_projected_months' => $projectedMonths, // Meses projetados
            'operational_cost_projected_total' => $projectedExpenses['total_monthly'] * $projectedMonths, // Total projetado
            'total_payable_consolidated' => $totalPayableConsolidated, // NOVA MÉTRICA: Total a Pagar Consolidado
            'total_cash_flow' => $cashFlowSummary['kpis']['fluxo_caixa_liquido'],
            'overdue_analysis' => [
                'overdue_count' => $accountsReceivable['payment_count'],
                'total_overdue' => $accountsReceivable['total_receivable'],
                'overdue_by_period' => [
                    '0-30' => 0, // TODO: implementar análise por período
                    '31-60' => 0,
                    '61-90' => 0,
                    '90+' => 0,
                ],
            ],
            'liquidity_index' => $cashFlowSummary['kpis']['indice_liquidez'],
            'operational_margin' => $dreSummary['kpis']['margem_percentual'],
            'commitment_rate' => 100 - $dreSummary['kpis']['margem_percentual'],
            'risk_level' => $cashFlowSummary['kpis']['nivel_risco'],
            // Strategic balance metrics
            'generated_cash' => $strategicBalance['generated_cash'],
            'committed_cash' => $strategicBalance['committed_cash'],
            'financial_balance' => $strategicBalance['financial_balance'],
        ];

        $periodMetrics = null;
        if (! $showGlobal && $startDate && $endDate && $cashFlowSummary && $dreSummary) {
            $periodMetrics = [
                'executive_summary' => [
                    'receivable' => $cashFlowSummary['kpis']['total_entradas'],
                    'payable_artists' => $cashFlowSummary['kpis']['total_saidas'] * 0.8,
                    'payable_bookers' => $cashFlowSummary['kpis']['total_saidas'] * 0.2,
                    'payable_expenses' => 0,
                    'total_payable' => $cashFlowSummary['kpis']['total_saidas'],
                    'net_cash_flow' => $cashFlowSummary['kpis']['fluxo_caixa_liquido'],
                    'health_score' => $this->calculateHealthScore($cashFlowSummary['kpis']['indice_liquidez']),
                    'period_days' => $startDate->diffInDays($endDate) + 1,
                    // Add keys expected by the partial view
                    'liquidity_index' => $cashFlowSummary['kpis']['indice_liquidez'],
                    'operational_margin' => $dreSummary['kpis']['margem_percentual'],
                    'commitment_rate' => 100 - $dreSummary['kpis']['margem_percentual'],
                    'cash_flow' => $cashFlowSummary['kpis']['fluxo_caixa_liquido'],
                ],
                'key_insights' => [
                    $cashFlowSummary['kpis']['status_financeiro'] === 'positivo'
                        ? 'Situação financeira saudável com fluxo de caixa positivo.'
                        : 'Atenção: fluxo de caixa negativo detectado.',
                    'Ticket médio dos eventos: R$ '.number_format($dreSummary['kpis']['ticket_medio'], 2, ',', '.'),
                ],
                'recommendations' => [
                    $cashFlowSummary['kpis']['fluxo_caixa_liquido'] >= 0
                        ? 'Mantenha estratégia atual - situação sólida.'
                        : 'Priorize cobrança de recebimentos pendentes.',
                ],
            ];
        }

        return view('projections.dashboard', [
            'start_date' => $startDate ? $startDate->format('Y-m-d') : Carbon::today()->format('Y-m-d'),
            'end_date' => $endDate ? $endDate->format('Y-m-d') : Carbon::today()->addDays(29)->format('Y-m-d'),
            'view_mode' => $viewMode,
            'dre_data' => $dreData,
            'cashflow_data' => $cashFlowData,
            'comparison_data' => $comparisonData,
            'global_metrics' => $globalMetrics,
            'period_metrics' => $periodMetrics,
            'period_listings' => null, // Compatibilidade
            'show_global' => $showGlobal,
            'accounts_receivable' => $accountsReceivable, // Dados para tabelas detalhadas
            'artist_payment_details' => $artistPaymentDetails, // Detalhes dos pagamentos aos artistas
            'booker_commission_details' => $bookerCommissionDetails, // Detalhes das comissões aos bookers
            'projected_expenses' => $projectedExpenses, // Detalhes das despesas previstas
            'strategic_balance' => $strategicBalance, // Novas métricas estratégicas
            'gig_expenses_details' => $gigExpenses, // Detalhes das despesas de eventos
            'operational_expenses_details' => $projectedExpenses, // Detalhes dos custos operacionais
        ]);
    }

    /**
     * Calcula contas a receber globais (sem restrição de período).
     *
     * IMPORTANTE: Respeita SoftDeletes - apenas inclui payments de gigs não-deletados.
     */
    private function calculateGlobalAccountsReceivable(): array
    {
        return $this->cacheService->rememberAccountsReceivable(function () {
            $pendingPayments = \App\Models\Payment::query()
                ->whereNull('confirmed_at')
                ->where('due_date', '>=', now()->subMonths(12)) // Últimos 12 meses para evitar dados muito antigos
                ->whereHas('gig') // Garante que apenas payments com gigs não-deletados sejam incluídos
                ->with('gig:id,contract_number,artist_id,gig_date,location_event_details', 'gig.artist:id,name')
                ->orderBy('due_date')
                ->get();

            $totalReceivable = $pendingPayments->sum('due_value_brl');

            // Separar pagamentos vencidos e futuros com base na DATA DO EVENTO
            $overduePayments = $pendingPayments->filter(function ($payment) {
                return $payment->gig && \Carbon\Carbon::parse($payment->gig->gig_date)->isPast();
            });

            $futurePayments = $pendingPayments->filter(function ($payment) {
                return ! $payment->gig || \Carbon\Carbon::parse($payment->gig->gig_date)->isFuture() || \Carbon\Carbon::parse($payment->gig->gig_date)->isToday();
            });

            $totalOverdue = $overduePayments->sum('due_value_brl');
            $totalFuture = $futurePayments->sum('due_value_brl');

            $paymentsByMonth = $pendingPayments->groupBy(function ($payment) {
                return \Carbon\Carbon::parse($payment->due_date)->format('Y-m');
            })->map(function ($payments, $yearMonth) {
                return [
                    'year_month' => $yearMonth,
                    'month_name' => \Carbon\Carbon::createFromFormat('Y-m', $yearMonth)->locale('pt_BR')->isoFormat('MMMM/YYYY'),
                    'total' => $payments->sum('due_value_brl'),
                    'count' => $payments->count(),
                ];
            })->sortKeys();

            return [
                'total_receivable' => $totalReceivable,
                'total_overdue' => $totalOverdue,
                'total_future' => $totalFuture,
                'payment_count' => $pendingPayments->count(),
                'overdue_count' => $overduePayments->count(),
                'future_count' => $futurePayments->count(),
                'by_month' => $paymentsByMonth->values(),
                'payments' => $pendingPayments->map(function ($payment) {
                    $isOverdue = $payment->gig && \Carbon\Carbon::parse($payment->gig->gig_date)->isPast();

                    return [
                        'payment_id' => $payment->id,
                        'gig_id' => $payment->gig_id,
                        'gig_contract' => $payment->gig->contract_number ?? 'N/A',
                        'artist_name' => $payment->gig->artist->name ?? 'N/A',
                        'location' => $payment->gig->location_event_details ?? 'N/A',
                        'due_date' => \Carbon\Carbon::parse($payment->due_date)->isoFormat('L'),
                        'due_value_brl' => $payment->due_value_brl,
                        'days_until_due' => \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($payment->due_date), false),
                        'is_overdue' => $isOverdue,
                    ];
                })->sortBy('due_date')->values(),
            ];
        });
    }

    /**
     * Calcula o total de despesas de eventos (GigCost).
     * Retorna despesas pendentes e confirmadas de todos os eventos.
     */
    private function calculateTotalGigExpenses(): array
    {
        return $this->cacheService->rememberGigExpenses(function () {
            // Despesas pendentes (não confirmadas)
            $pendingExpenses = \App\Models\GigCost::query()
                ->where('is_confirmed', false)
                ->whereHas('gig') // Garante que apenas custos de gigs não-deletados sejam incluídos
                ->with('gig:id,gig_date,contract_number,artist_id,location_event_details', 'gig.artist:id,name', 'costCenter:id,name')
                ->get();

            // Despesas confirmadas
            $confirmedExpenses = \App\Models\GigCost::query()
                ->where('is_confirmed', true)
                ->whereHas('gig')
                ->with('gig:id,gig_date,contract_number,artist_id,location_event_details', 'gig.artist:id,name', 'costCenter:id,name')
                ->get();

            $totalPending = $pendingExpenses->sum('value_brl');
            $totalConfirmed = $confirmedExpenses->sum('value_brl');

            $mapExpenses = function ($cost) {
                return [
                    'gig_id' => $cost->gig_id,
                    'gig_date_raw' => $cost->gig ? $cost->gig->gig_date : null,
                    'gig_date' => $cost->gig ? $cost->gig->gig_date->isoFormat('L') : 'N/A',
                    'gig_contract' => $cost->gig ? ($cost->gig->contract_number ?? "Gig #{$cost->gig->id}") : 'N/A',
                    'artist_name' => $cost->gig && $cost->gig->artist ? $cost->gig->artist->name : 'N/A',
                    'location' => $cost->gig ? ($cost->gig->location_event_details ?? 'N/A') : 'N/A',
                    'description' => $cost->description,
                    'value_brl' => $cost->value_brl,
                    'is_confirmed' => $cost->is_confirmed,
                ];
            };

            $pending = $pendingExpenses->map($mapExpenses);
            $confirmed = $confirmedExpenses->map($mapExpenses);

            return [
                'total_expenses' => $totalPending + $totalConfirmed,
                'total_pending' => $totalPending,
                'total_confirmed' => $totalConfirmed,
                'pending_count' => $pendingExpenses->count(),
                'confirmed_count' => $confirmedExpenses->count(),
                'pending' => $pending,
                'confirmed' => $confirmed,
            ];
        });
    }

    /**
     * Calcula as novas métricas estratégicas de balanço.
     */
    private function calculateStrategicBalance(): array
    {
        return $this->cacheService->rememberStrategicBalance(function () {
            // 1. Obter Gigs passadas e futuras com eager loading para evitar N+1
            $pastGigs = Gig::where('gig_date', '<', today())
                ->with(['payments', 'settlement', 'gigCosts', 'artist:id,name', 'booker:id,name'])
                ->get();
            $futureGigs = Gig::where('gig_date', '>=', today())
                ->with(['payments', 'settlement', 'gigCosts', 'artist:id,name', 'booker:id,name'])
                ->get();

            // Obter valor mensal de custos operacionais
            $monthlyFixedCost = AgencyFixedCost::where('is_active', true)->sum('monthly_value');

            // 2. Calcular "Caixa Gerado (Eventos Passados)"
            $pastInflows = Payment::whereIn('gig_id', $pastGigs->pluck('id'))->whereNotNull('confirmed_at')->get()->sum('received_value_actual_brl');
            $pastArtistOutflows = Settlement::whereIn('gig_id', $pastGigs->pluck('id'))->sum('artist_payment_value');
            $pastBookerOutflows = Settlement::whereIn('gig_id', $pastGigs->pluck('id'))->sum('booker_commission_value_paid');

            // Calcular custos operacionais proporcionalmente ao período dos eventos passados
            $pastOperationalExpenses = 0;
            if ($pastGigs->isNotEmpty()) {
                $oldestGigDate = \Carbon\Carbon::parse($pastGigs->min('gig_date'));
                $monthsSpan = max(1, $oldestGigDate->diffInMonths(\Carbon\Carbon::today()) + 1);
                $pastOperationalExpenses = $monthlyFixedCost * $monthsSpan;
            }

            $generatedCash = $pastInflows - $pastArtistOutflows - $pastBookerOutflows - $pastOperationalExpenses;

            // 3. Calcular "Caixa Comprometido (Eventos Futuros)"
            $futureInflows = Payment::whereIn('gig_id', $futureGigs->pluck('id'))->whereNull('confirmed_at')->get()->sum('due_value_brl');
            $futureArtistOutflows = 0;
            $futureBookerOutflows = 0;
            foreach ($futureGigs as $gig) {
                $futureArtistOutflows += $this->cashFlowService->getGigCalculator()->calculateArtistInvoiceValueBrl($gig);
                $futureBookerOutflows += $this->cashFlowService->getGigCalculator()->calculateBookerCommissionBrl($gig);
            }

            // Calcular custos operacionais proporcionalmente ao período dos eventos futuros
            $futureOperationalExpenses = 0;
            if ($futureGigs->isNotEmpty()) {
                $furthestGigDate = \Carbon\Carbon::parse($futureGigs->max('gig_date'));
                $monthsSpan = max(1, \Carbon\Carbon::today()->diffInMonths($furthestGigDate) + 1);
                $futureOperationalExpenses = $monthlyFixedCost * $monthsSpan;
            }

            $committedCash = $futureInflows - $futureArtistOutflows - $futureBookerOutflows - $futureOperationalExpenses;

            // 4. Calcular "Balanço Financeiro"
            $financialBalance = $generatedCash + $committedCash;

            return [
                'generated_cash' => $generatedCash,
                'committed_cash' => $committedCash,
                'financial_balance' => $financialBalance,
            ];
        });
    }

    /**
     * Limpa o cache de projeções financeiras.
     * Deve ser chamado após atualizações em Gigs, Payments, Settlements ou Costs.
     */
    public static function clearCache(): void
    {
        app(ProjectionCacheService::class)->clearAll();
    }

    /**
     * Calcula pontuação de saúde financeira (0-100).
     */
    private function calculateHealthScore(float $liquidityIndex): int
    {
        if ($liquidityIndex >= 1.5) {
            return 100;
        }
        if ($liquidityIndex >= 1.2) {
            return 85;
        }
        if ($liquidityIndex >= 1.0) {
            return 70;
        }
        if ($liquidityIndex >= 0.8) {
            return 50;
        }
        if ($liquidityIndex >= 0.5) {
            return 30;
        }

        return 10;
    }

    /**
     * Exibe detalhes da DRE Projetada.
     */
    public function dreDetails(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $this->dreService->setPeriod($startDate, $endDate);

        $dreTotal = $this->dreService->calculateTotalDre();
        $ticketMedio = $this->dreService->calculateTicketMedio();
        $breakEvenPoint = $this->dreService->calculateBreakEvenPoint();

        return view('projections.dre-details', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'dre_total' => $dreTotal,
            'ticket_medio' => $ticketMedio,
            'break_even_point' => $breakEvenPoint,
        ]);
    }

    /**
     * Exibe detalhes do Fluxo de Caixa Projetado.
     */
    public function cashFlowDetails(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $this->cashFlowService->setPeriod($startDate, $endDate);

        $cashFlowTotal = $this->cashFlowService->calculateTotalCashFlow();
        $accountsReceivable = $this->cashFlowService->calculateAccountsReceivable();

        return view('projections.cashflow-details', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'cashflow_total' => $cashFlowTotal,
            'accounts_receivable' => $accountsReceivable,
        ]);
    }

    /**
     * API endpoint para retornar métricas consolidadas (JSON).
     */
    public function apiMetrics(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $this->dreService->setPeriod($startDate, $endDate);
        $this->cashFlowService->setPeriod($startDate, $endDate);

        return response()->json([
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'dre' => $this->dreService->getExecutiveSummary(),
            'cashflow' => $this->cashFlowService->getExecutiveSummary(),
            'comparison' => $this->cashFlowService->compareWithDre(),
        ]);
    }

    /**
     * Página de debug com cálculos detalhados (mantida para compatibilidade).
     */
    public function debug(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::today();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::today()->addMonths(3);

        $this->dreService->setPeriod($startDate, $endDate);
        $this->cashFlowService->setPeriod($startDate, $endDate);

        $debugData = [
            'DRE Projetada (Competência)' => [
                'value' => $this->dreService->calculateTotalDre(),
                'description' => 'Demonstração do Resultado do Exercício com base na data de execução dos eventos',
            ],
            'Fluxo de Caixa (Caixa)' => [
                'value' => $this->cashFlowService->calculateTotalCashFlow(),
                'description' => 'Fluxo de Caixa com base nas datas reais de recebimento',
            ],
            'Comparação DRE vs Fluxo' => [
                'value' => $this->cashFlowService->compareWithDre(),
                'description' => 'Análise das diferenças entre regime de competência e caixa',
            ],
            'KPIs' => [
                'value' => [
                    'ticket_medio' => $this->dreService->calculateTicketMedio(),
                    'break_even_point' => $this->dreService->calculateBreakEvenPoint(),
                ],
                'description' => 'Indicadores chave de performance',
            ],
        ];

        return view('projections.debug', [
            'debug_data' => $debugData,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);
    }
}
