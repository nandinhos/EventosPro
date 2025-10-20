<?php

namespace App\Http\Controllers;

use App\Services\FinancialProjectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialProjectionController extends Controller
{
    protected $projectionService;

    public function __construct(FinancialProjectionService $projectionService)
    {
        $this->projectionService = $projectionService;
    }

    public function index(Request $request)
    {
        // Valida os inputs
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'show_global' => 'nullable|boolean',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $showGlobal = $request->boolean('show_global', false);

        // MÉTRICAS GERAIS (sempre carregadas - panorama completo sem filtro de período)
        // Configurar período global (tudo: passado + futuro)
        $this->projectionService->setPeriod('', Carbon::create(2000, 1, 1)->format('Y-m-d'), Carbon::create(2100, 12, 31)->format('Y-m-d'));

        $globalMetrics = [
            'total_receivable' => $this->projectionService->getAccountsReceivable(),
            'total_payable_artists' => $this->projectionService->getAccountsPayableArtists(),
            'total_payable_bookers' => $this->projectionService->getAccountsPayableBookers(),
            'total_payable_expenses' => $this->projectionService->getAccountsPayableExpenses(),
            'total_cash_flow' => $this->projectionService->getProjectedCashFlow(),
            'overdue_analysis' => $this->projectionService->getOverdueAnalysis(),
        ];

        // Calcular métricas gerenciais globais
        $totalPayable = $globalMetrics['total_payable_artists'] + $globalMetrics['total_payable_bookers'] + $globalMetrics['total_payable_expenses'];
        $liquidityIndex = $totalPayable > 0 ? ($globalMetrics['total_receivable'] / $totalPayable) : 0;
        $operationalMargin = $globalMetrics['total_receivable'] > 0 ? (($globalMetrics['total_cash_flow'] / $globalMetrics['total_receivable']) * 100) : 0;
        $commitmentRate = $globalMetrics['total_receivable'] > 0 ? (($totalPayable / $globalMetrics['total_receivable']) * 100) : 0;

        // Análise de risco global
        $riskLevel = 'low';
        if ($liquidityIndex < 1.0 || ($globalMetrics['total_cash_flow'] < 0 && abs($globalMetrics['total_cash_flow']) > ($globalMetrics['total_receivable'] * 0.2))) {
            $riskLevel = 'high';
        } elseif ($liquidityIndex < 1.2 || ($globalMetrics['total_cash_flow'] < 0 && abs($globalMetrics['total_cash_flow']) <= ($globalMetrics['total_receivable'] * 0.2))) {
            $riskLevel = 'medium';
        }

        $globalMetrics['liquidity_index'] = $liquidityIndex;
        $globalMetrics['operational_margin'] = $operationalMargin;
        $globalMetrics['commitment_rate'] = $commitmentRate;
        $globalMetrics['risk_level'] = $riskLevel;

        // MÉTRICAS POR PERÍODO (apenas se datas foram fornecidas ou se global foi solicitado)
        $periodMetrics = null;
        $periodListings = null;

        if (($startDate && $endDate) || $showGlobal) {
            // Define período customizado ou global
            if ($showGlobal) {
                $this->projectionService->setPeriod('', Carbon::create(2000, 1, 1)->format('Y-m-d'), Carbon::create(2100, 12, 31)->format('Y-m-d'));
            } else {
                $this->projectionService->setPeriod('', $startDate, $endDate);
            }

            // Obtém métricas do período
            $executiveSummary = $this->projectionService->getExecutiveSummary();
            $futureEventsAnalysis = $this->projectionService->getFutureEventsAnalysis();
            $comparativeAnalysis = $this->projectionService->getComparativePeriodAnalysis();

            $periodMetrics = [
                'executive_summary' => $executiveSummary,
                'future_events_analysis' => $futureEventsAnalysis,
                'comparative_analysis' => $comparativeAnalysis,
            ];

            // Carrega listagens detalhadas
            $artistGigs = $this->projectionService->getUpcomingInternalPayments('artists');
            $bookerGigs = $this->projectionService->getUpcomingInternalPayments('bookers');

            $periodListings = [
                'upcoming_client_payments' => $this->projectionService->getUpcomingClientPayments(),
                'upcoming_artist_payments' => $artistGigs->map(function ($gig) {
                    return [
                        'artist_name' => $gig->artist->name ?? 'N/A',
                        'event_name' => $gig->event_name ?? 'Evento #'.$gig->id,
                        'gig_date' => $gig->gig_date,
                        'amount' => app(\App\Services\GigFinancialCalculatorService::class)->calculateArtistInvoiceValueBrl($gig),
                    ];
                }),
                'upcoming_booker_payments' => $bookerGigs->map(function ($gig) {
                    return [
                        'booker_name' => $gig->booker->name ?? 'N/A',
                        'event_name' => $gig->event_name ?? 'Evento #'.$gig->id,
                        'gig_date' => $gig->gig_date,
                        'amount' => app(\App\Services\GigFinancialCalculatorService::class)->calculateBookerCommissionBrl($gig),
                    ];
                }),
                'projected_expenses_by_cost_center' => $this->projectionService->getProjectedExpensesByCostCenter(),
            ];
        }

        return view('projections.dashboard', [
            'global_metrics' => $globalMetrics,
            'period_metrics' => $periodMetrics,
            'period_listings' => $periodListings,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'show_global' => $showGlobal,
        ]);
    }

    /**
     * Exibe uma página de depuração com todos os cálculos de projeção.
     */
    public function debug(Request $request): View
    {
        // Valida os inputs
        $validated = $request->validate([
            'period' => 'nullable|string|in:30_days,60_days,90_days,next_semester,next_year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Pega o período do request ou usa o default
        $period = $request->input('period', '30_days');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $this->projectionService->setPeriod($period, $startDate, $endDate);

        // Armazena todos os resultados dos cálculos em um array
        $debugData = [
            'Contas a Receber (Clientes)' => [
                'value' => $this->projectionService->getAccountsReceivable(),
                'items' => $this->projectionService->getUpcomingPayments('clients'),
            ],
            'Contas a Pagar (Artistas)' => [
                'value' => $this->projectionService->getAccountsPayableArtists(),
                'items' => $this->projectionService->getUpcomingPayments('artists'),
            ],
            'Contas a Pagar (Bookers)' => [
                'value' => $this->projectionService->getAccountsPayableBookers(),
                'items' => $this->projectionService->getUpcomingPayments('bookers'),
            ],
            'Contas a Pagar (Despesas Previstas)' => [
                'value' => $this->projectionService->getAccountsPayableExpenses(),
                'items' => $this->projectionService->getProjectedExpensesByCostCenter(),
            ],
            'Fluxo de Caixa Projetado' => [
                'value' => $this->projectionService->getProjectedCashFlow(),
                'items' => null, // Não há itens detalhados para o total
            ],
        ];

        return view('projections.debug', [
            'debugData' => $debugData,
            'period' => $period,
        ]);
    }
}
