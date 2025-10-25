<?php

namespace App\Http\Controllers;

use App\Services\FinancialProjectionService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

            // Obtém métricas otimizadas do período usando MCP
            $periodMetrics = $this->getOptimizedPeriodMetrics($startDate, $endDate, $showGlobal);

            // Carrega listagens detalhadas sempre que houver período selecionado
            $periodListings = $this->getPeriodSummary();
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
     * Obtém métricas otimizadas do período focadas em tomada de decisão.
     */
    private function getOptimizedPeriodMetrics(?string $startDate, ?string $endDate, bool $showGlobal): array
    {
        // Cache key baseado nos parâmetros
        $cacheKey = 'period_metrics_'.md5($startDate.$endDate.$showGlobal);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($startDate, $endDate, $showGlobal) {
            // Métricas essenciais para tomada de decisão
            $receivable = $this->projectionService->getAccountsReceivable();
            $payableArtists = $this->projectionService->getAccountsPayableArtists();
            $payableBookers = $this->projectionService->getAccountsPayableBookers();
            $payableExpenses = $this->projectionService->getAccountsPayableExpenses();

            $totalPayable = $payableArtists + $payableBookers + $payableExpenses;
            $netCashFlow = $receivable - $totalPayable;

            // Análise de saúde financeira simplificada
            $healthScore = $this->calculateFinancialHealthScore($receivable, $totalPayable);

            return [
                'executive_summary' => [
                    'receivable' => $receivable,
                    'payable_artists' => $payableArtists,
                    'payable_bookers' => $payableBookers,
                    'payable_expenses' => $payableExpenses,
                    'total_payable' => $totalPayable,
                    'net_cash_flow' => $netCashFlow,
                    'health_score' => $healthScore,
                    'period_days' => $showGlobal ? 'Global' : $this->calculatePeriodDays($startDate, $endDate),
                ],
                'key_insights' => $this->generateKeyInsights($receivable, $totalPayable, $healthScore),
                'recommendations' => $this->generateRecommendations($netCashFlow, $healthScore),
            ];
        });
    }

    /**
     * Calcula pontuação de saúde financeira (0-100).
     */
    private function calculateFinancialHealthScore(float $receivable, float $payable): int
    {
        if ($payable <= 0) {
            return 100;
        }

        $ratio = $receivable / $payable;

        if ($ratio >= 1.5) {
            return 100;
        }     // Excelente
        if ($ratio >= 1.2) {
            return 85;
        }      // Muito bom
        if ($ratio >= 1.0) {
            return 70;
        }      // Bom
        if ($ratio >= 0.8) {
            return 50;
        }      // Regular
        if ($ratio >= 0.5) {
            return 30;
        }      // Ruim

        return 10;                         // Crítico
    }

    /**
     * Calcula dias do período.
     */
    private function calculatePeriodDays(?string $startDate, ?string $endDate): int
    {
        if (! $startDate || ! $endDate) {
            return 0;
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return $start->diffInDays($end) + 1;
    }

    /**
     * Gera insights chave baseados nos dados.
     */
    private function generateKeyInsights(float $receivable, float $payable, int $healthScore): array
    {
        $insights = [];

        if ($healthScore >= 80) {
            $insights[] = 'Situação financeira saudável com bom fluxo de caixa.';
        } elseif ($healthScore >= 60) {
            $insights[] = 'Situação financeira estável, mas monitore os recebimentos.';
        } else {
            $insights[] = 'Atenção necessária: fluxo de caixa negativo detectado.';
        }

        $receivablePercentage = $payable > 0 ? ($receivable / $payable) * 100 : 0;
        $insights[] = "Recebimentos representam {$receivablePercentage}% dos pagamentos projetados.";

        return $insights;
    }

    /**
     * Gera recomendações baseadas na análise.
     */
    private function generateRecommendations(float $netCashFlow, int $healthScore): array
    {
        $recommendations = [];

        if ($netCashFlow < 0) {
            $recommendations[] = 'Priorize cobrança de recebimentos pendentes.';
            $recommendations[] = 'Reveja cronograma de pagamentos para otimizar fluxo.';
        }

        if ($healthScore < 50) {
            $recommendations[] = 'Considere renegociar prazos com fornecedores.';
            $recommendations[] = 'Avalie necessidade de capital de giro adicional.';
        }

        if ($healthScore > 80) {
            $recommendations[] = 'Mantenha estratégia atual - situação sólida.';
        }

        return array_slice($recommendations, 0, 3); // Máximo 3 recomendações
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
            $dueDate = Carbon::parse($payment->due_date);
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

            // Agrupar gigs por mês para melhor visualização
            $gigsByMonth = $group->groupBy(function ($gig) {
                return Carbon::parse($gig->gig_date)->format('m/Y');
            })->map(function ($gigsInMonth) {
                $monthName = Carbon::parse($gigsInMonth->first()->gig_date)->format('M/Y');
                $totalContractMonth = $gigsInMonth->sum('cache_value_brl');
                $totalGrossCashMonth = $gigsInMonth->sum(fn ($gig) => $this->calculatorService->calculateGrossCashBrl($gig));

                return [
                    'month_name' => $monthName,
                    'month_total_contract' => $totalContractMonth,
                    'month_total_gross_cash' => $totalGrossCashMonth,
                    'month_gigs_count' => $gigsInMonth->count(),
                    'gigs' => $gigsInMonth->map(function ($gig) {
                        $gross_cash_brl = $this->calculatorService->calculateGrossCashBrl($gig);

                        return [
                            'gig_id' => $gig->id,
                            'sale_date' => Carbon::parse($gig->sale_date)->format('d/m/Y'),
                            'gig_date' => $gig->gig_date->format('d/m/Y'),
                            'artist_name' => $gig->artist->name ?? 'N/A',
                            'location_event_details' => $gig->location_event_details,
                            'contract_value' => $gig->cache_value_brl,
                            'gross_cash_brl' => $gross_cash_brl,
                        ];
                    }),
                ];
            })->sortKeys();

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
                'gigs_by_month' => $gigsByMonth,
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

            // Agrupar gigs por mês para melhor visualização
            $gigsByMonth = $group->groupBy(function ($gig) {
                return Carbon::parse($gig->gig_date)->format('m/Y');
            })->map(function ($gigsInMonth) {
                $monthName = Carbon::parse($gigsInMonth->first()->gig_date)->format('M/Y');
                $totalContractMonth = $gigsInMonth->sum('cache_value_brl');
                $totalGrossCashMonth = $gigsInMonth->sum(fn ($gig) => $this->calculatorService->calculateGrossCashBrl($gig));
                $totalBookerCommissionMonth = $gigsInMonth->sum(fn ($gig) => $this->calculatorService->calculateBookerCommissionBrl($gig));

                return [
                    'month_name' => $monthName,
                    'month_total_contract' => $totalContractMonth,
                    'month_total_gross_cash' => $totalGrossCashMonth,
                    'month_total_booker_commission' => $totalBookerCommissionMonth,
                    'month_gigs_count' => $gigsInMonth->count(),
                    'gigs' => $gigsInMonth->map(function ($gig) {
                        $gross_cash_brl = $this->calculatorService->calculateGrossCashBrl($gig);
                        $booker_commission_brl = $this->calculatorService->calculateBookerCommissionBrl($gig);

                        return [
                            'gig_id' => $gig->id,
                            'sale_date' => Carbon::parse($gig->sale_date)->format('d/m/Y'),
                            'gig_date' => $gig->gig_date->format('d/m/Y'),
                            'artist_name' => $gig->artist->name ?? 'N/A',
                            'location_event_details' => $gig->location_event_details,
                            'contract_value' => $gig->cache_value_brl,
                            'gross_cash_brl' => $gross_cash_brl,
                            'booker_commission_brl' => $booker_commission_brl,
                        ];
                    }),
                ];
            })->sortKeys();

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
                'gigs_by_month' => $gigsByMonth,
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
