<?php

namespace App\Http\Controllers;

use App\Services\FinancialProjectionService;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialProjectionController extends Controller
{
    protected $projectionService;

    protected $calculatorService;

    public function __construct(
        FinancialProjectionService $projectionService,
        GigFinancialCalculatorService $calculatorService
    ) {
        $this->projectionService = $projectionService;
        $this->calculatorService = $calculatorService;
    }

    /**
     * Exibe o dashboard de projeções financeiras.
     */
    public function index(Request $request): View
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

        // MÉTRICAS GERAIS (sempre carregadas - panorama completo)
        $globalMetrics = $this->projectionService->getGlobalMetrics();

        // MÉTRICAS POR PERÍODO (apenas se datas foram fornecidas ou se global foi solicitado)
        $periodMetrics = null;
        $periodListings = null;

        if (($startDate && $endDate) || $showGlobal) {
            // Define período customizado ou global
            if ($showGlobal) {
                $this->projectionService->setPeriod('', '2000-01-01', '2100-12-31');
            } else {
                $this->projectionService->setPeriod('', $startDate, $endDate);
            }

            // Obtém métricas do período apenas se necessário
            // Otimizado: carrega apenas métricas essenciais para reduzir carga
            $executiveSummary = $this->projectionService->getExecutiveSummary();

            $periodMetrics = [
                'executive_summary' => $executiveSummary,
                'future_events_analysis' => null, // Carregado apenas se necessário
                'comparative_analysis' => null,   // Carregado apenas se necessário
            ];

            // Carrega listagens detalhadas apenas se solicitado período específico
            if ($startDate && $endDate && ! $showGlobal) {
                $periodListings = $this->getPeriodSummary();
            }
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

        $period = $request->input('period', '30_days');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $this->projectionService->setPeriod($period, $startDate, $endDate);

        // Armazena todos os resultados dos cálculos em um array
        $debugData = [
            'Contas a Receber (Clientes)' => [
                'value' => $this->projectionService->getAccountsReceivable(),
                'items' => $this->projectionService->getUpcomingClientPayments(),
            ],
            'Contas a Pagar (Artistas)' => [
                'value' => $this->projectionService->getAccountsPayableArtists(),
                'items' => $this->projectionService->getUpcomingInternalPayments('artists'),
            ],
            'Contas a Pagar (Bookers)' => [
                'value' => $this->projectionService->getAccountsPayableBookers(),
                'items' => $this->projectionService->getUpcomingInternalPayments('bookers'),
            ],
            'Contas a Pagar (Despesas Previstas)' => [
                'value' => $this->projectionService->getAccountsPayableExpenses(),
                'items' => $this->projectionService->getProjectedExpensesByCostCenter(),
            ],
            'Fluxo de Caixa Projetado' => [
                'value' => $this->projectionService->getProjectedCashFlow(),
                'items' => null,
            ],
        ];

        return view('projections.debug', [
            'debugData' => $debugData,
            'period' => $period,
        ]);
    }

    /**
     * Obtém sumário consolidado do período com agrupamentos e subtotais.
     */
    private function getPeriodSummary(): array
    {
        // 1. CONTAS A RECEBER (Pagamentos de Clientes)
        $clientPayments = $this->projectionService->getUpcomingClientPayments();

        // Otimizado: retorna array vazio se não há dados
        if ($clientPayments->isEmpty()) {
            return [
                'receivable' => ['total' => 0, 'count' => 0, 'grouped' => collect(), 'items' => collect()],
                'artists' => ['total' => 0, 'count' => 0, 'grouped' => collect(), 'items' => collect()],
                'bookers' => ['total' => 0, 'count' => 0, 'grouped' => collect(), 'items' => collect()],
                'expenses' => ['total' => 0, 'count' => 0, 'grouped' => collect(), 'items' => collect()],
            ];
        }

        $receivableTotal = (float) $clientPayments->sum('due_value_brl');

        // Agrupar por status de vencimento
        $today = now()->startOfDay();
        $receivableGrouped = $clientPayments->groupBy(function ($payment) use ($today) {
            $dueDate = \Carbon\Carbon::parse($payment->due_date);
            if ($dueDate->lt($today)) {
                return 'vencido';
            } elseif ($dueDate->lte($today->copy()->addDays(7))) {
                return 'vence_7_dias';
            } elseif ($dueDate->lte($today->copy()->addDays(30))) {
                return 'vence_30_dias';
            } else {
                return 'a_vencer';
            }
        })->map(function ($group, $status) {
            $statusLabels = [
                'vencido' => 'Vencidos',
                'vence_7_dias' => 'Vencem em 7 dias',
                'vence_30_dias' => 'Vencem em 30 dias',
                'a_vencer' => 'A vencer (30+ dias)',
            ];

            return [
                'status' => $status,
                'label' => $statusLabels[$status] ?? $status,
                'items' => $group,
                'subtotal' => (float) $group->sum('due_value_brl'),
                'count' => $group->count(),
            ];
        });

        // 2. PAGAMENTOS A ARTISTAS
        $artistGigs = $this->projectionService->getUpcomingInternalPayments('artists');
        $artistsTotal = 0;

        // Agrupar por artista
        $artistsGrouped = $artistGigs->groupBy(function ($gig) {
            return $gig->artist_id ?? 'sem_artista';
        })->map(function ($group, $artistId) use (&$artistsTotal) {
            $artistName = $group->first()->artist->name ?? 'Sem Artista';
            $subtotal = 0;

            $items = $group->map(function ($gig) use (&$subtotal) {
                $amount = $this->calculatorService->calculateArtistInvoiceValueBrl($gig);
                $subtotal += $amount;

                return [
                    'gig_id' => $gig->id,
                    'artist_name' => $gig->artist->name ?? 'N/A',
                    'event_name' => $gig->location_event_details ?? 'Evento #'.$gig->id,
                    'gig_date' => $gig->gig_date,
                    'amount' => $amount,
                ];
            });

            $artistsTotal += $subtotal;

            return [
                'artist_id' => $artistId,
                'artist_name' => $artistName,
                'items' => $items,
                'subtotal' => $subtotal,
                'count' => $group->count(),
            ];
        })->sortByDesc('subtotal');

        // 3. COMISSÕES DE BOOKERS
        $bookerGigs = $this->projectionService->getUpcomingInternalPayments('bookers');
        $bookersTotal = 0;

        // Agrupar por booker
        $bookersGrouped = $bookerGigs->groupBy(function ($gig) {
            return $gig->booker_id ?? 'sem_booker';
        })->map(function ($group, $bookerId) use (&$bookersTotal) {
            $bookerName = $group->first()->booker->name ?? 'Sem Booker';
            $subtotal = 0;

            $items = $group->map(function ($gig) use (&$subtotal) {
                $amount = $this->calculatorService->calculateBookerCommissionBrl($gig);
                $subtotal += $amount;

                return [
                    'gig_id' => $gig->id,
                    'booker_name' => $gig->booker->name ?? 'N/A',
                    'event_name' => $gig->location_event_details ?? 'Evento #'.$gig->id,
                    'gig_date' => $gig->gig_date,
                    'amount' => $amount,
                ];
            });

            $bookersTotal += $subtotal;

            return [
                'booker_id' => $bookerId,
                'booker_name' => $bookerName,
                'items' => $items,
                'subtotal' => $subtotal,
                'count' => $group->count(),
            ];
        })->sortByDesc('subtotal');

        // 4. DESPESAS POR CENTRO DE CUSTO (já vem agrupado do service)
        $expensesByCostCenter = $this->projectionService->getProjectedExpensesByCostCenter();
        $expensesTotal = (float) $expensesByCostCenter->sum('total_brl');

        // Retornar estrutura consolidada
        return [
            'receivable' => [
                'total' => $receivableTotal,
                'count' => $clientPayments->count(),
                'grouped' => $receivableGrouped,
                'items' => $clientPayments,
            ],
            'artists' => [
                'total' => $artistsTotal,
                'count' => $artistGigs->count(),
                'grouped' => $artistsGrouped,
                'items' => $artistGigs,
            ],
            'bookers' => [
                'total' => $bookersTotal,
                'count' => $bookerGigs->count(),
                'grouped' => $bookersGrouped,
                'items' => $bookerGigs,
            ],
            'expenses' => [
                'total' => $expensesTotal,
                'count' => $expensesByCostCenter->sum(function ($group) {
                    return $group['expenses']->count();
                }),
                'grouped' => $expensesByCostCenter,
                'items' => $expensesByCostCenter,
            ],
        ];
    }
}
