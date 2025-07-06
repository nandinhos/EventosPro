<?php

namespace App\Services;

use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Payment;
use App\Models\Settlement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FinancialReportService
{
    protected $startDate;
    protected $endDate;
    protected $filters;
    protected $calculator;

    public function __construct(GigFinancialCalculatorService $calculator)
    {
        $this->calculator = $calculator;
        $this->setDefaultPeriod();
        $this->filters = [];
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        $this->startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $this->endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now()->endOfMonth();
    }

    protected function setDefaultPeriod()
    {
        $this->startDate = Carbon::now()->startOfMonth();
        $this->endDate = Carbon::now()->endOfMonth();
    }

    protected function applyFilters($query)
    {
        $query->whereBetween('gig_date', [$this->startDate, $this->endDate]);

        if (isset($this->filters['booker_id'])) {
            $query->where('booker_id', $this->filters['booker_id']);
        }
        if (isset($this->filters['artist_id'])) {
            $query->where('artist_id', $this->filters['artist_id']);
        }
       

        return $query;
    }

    public function getOverviewSummary(): array
{
    $gigs = Gig::with(['payments', 'costs'])
        ->whereBetween('gig_date', [$this->startDate, $this->endDate])
        ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
        ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
        ->get();

    $totalInflow = $gigs->sum(function ($gig) {
        return $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
    });
    $totalOutflow = $gigs->sum(function ($gig) {
        return $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
    });
    $netCashflow = $totalInflow - $totalOutflow;

    return [
        'total_inflow' => $totalInflow ?: 0,
        'total_outflow' => $totalOutflow ?: 0,
        'net_cashflow' => $netCashflow ?: 0,
    ];
}

    public function getOverviewTableData(): Collection
    {
        $gigs = Gig::with(['artist', 'booker', 'payments', 'costs'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get();

        return $gigs->map(function ($gig) {
            try {
                $revenue = $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
                $costs = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                $commission = $this->calculator->calculateBookerCommissionBrl($gig);
                return [
                    'contract_number' => $gig->contract_number ?? 'N/A',
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'artist' => $gig->artist->name ?? 'N/A',
                    'booker' => $gig->booker->name ?? 'N/A',
                    'revenue' => $revenue,
                    'costs' => $costs,
                    'commission' => $commission,
                    'net_profit' => $revenue - ($costs + $commission),
                ];
            } catch (\Exception $e) {
                Log::error("Erro ao mapear dados da Gig ID {$gig->id}: " . $e->getMessage());
                return [
                    'contract_number' => 'Erro',
                    'gig_date' => 'N/A',
                    'artist' => 'N/A',
                    'booker' => 'N/A',
                    'revenue' => 0,
                    'costs' => 0,
                    'commission' => 0,
                    'net_profit' => 0,
                ];
            }
        });
    }

    public function getProfitabilitySummary(): array
    {
        $gigs = Gig::with(['payments', 'costs'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get();

        $totalProfit = 0;
        $totalRevenue = 0;
        $profitableEvents = 0;

        foreach ($gigs as $gig) {
            try {
                $revenue = $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
                $costs = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                $commission = $this->calculator->calculateBookerCommissionBrl($gig);
                $profit = $revenue - ($costs + $commission);

                $totalProfit += $profit;
                $totalRevenue += $revenue;
                if ($profit > 0) {
                    $profitableEvents++;
                }
            } catch (\Exception $e) {
                Log::error("Erro ao calcular resumo de rentabilidade para Gig ID {$gig->id}: " . $e->getMessage());
            }
        }

        $averageMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return [
            'total_profit' => $totalProfit,
            'average_margin' => $averageMargin,
            'profitable_events' => $profitableEvents,
        ];
    }

    public function getProfitabilityTableData(): Collection
{
    return Gig::with(['payments', 'costs', 'artist'])
        ->whereBetween('gig_date', [$this->startDate, $this->endDate])
        ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
        ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
        ->get()
        ->map(function ($gig) {
            try {
                $revenue = $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
                $costs = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                $commission = $gig->agency_commission_value ?? 0;
                $profit = $revenue - $costs - $commission;
                $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                return [
                    'contract_number' => $gig->contract_number ?? 'N/A',
                    'artist' => $gig->artist->name ?? 'N/A', // Adiciona o nome do artista
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'revenue' => $revenue,
                    'costs' => $costs,
                    'profit' => $profit,
                    'margin' => $margin,
                ];
            } catch (\Exception $e) {
                Log::error("Erro ao mapear dados de rentabilidade para Gig ID {$gig->id}: " . $e->getMessage());
                return [
                    'contract_number' => 'Erro',
                    'artist' => 'N/A',
                    'gig_date' => 'N/A',
                    'revenue' => 0,
                    'costs' => 0,
                    'profit' => 0,
                    'margin' => 0,
                ];
            }
        });
}

    /**
     * Obtém o resumo para a aba de Fluxo de Caixa.
     * (Mantém a lógica que já corrigimos, baseada em datas de transação)
     */
    public function getCashflowSummary(): array
    {
        // 1. Entradas: Pagamentos de clientes confirmados no período
        $totalInflow = Payment::whereNotNull('confirmed_at')
            ->whereBetween('received_date_actual', [$this->startDate, $this->endDate])
            ->sum(DB::raw('COALESCE(received_value_actual, 0)'));

        // 2. Saídas (Despesas): Custos confirmados no período
        $totalOutflowExpenses = GigCost::where('is_confirmed', true)
            ->whereBetween('confirmed_at', [$this->startDate, $this->endDate])
            ->sum('value');

        // 3. Saídas (Acertos): Pagamentos a artistas e bookers no período
        $totalOutflowArtists = Settlement::whereBetween('artist_payment_paid_at', [$this->startDate, $this->endDate])
            ->sum('artist_payment_value');
        $totalOutflowBookers = Settlement::whereBetween('booker_commission_paid_at', [$this->startDate, $this->endDate])
            ->sum('booker_commission_value_paid');

        // 4. Consolidação
        $totalOutflow = $totalOutflowExpenses + $totalOutflowArtists + $totalOutflowBookers;
        $netCashflow = $totalInflow - $totalOutflow;

        return [
            'total_inflow' => $totalInflow,
            'total_outflow' => $totalOutflow,
            'total_outflow_expenses' => $totalOutflowExpenses,
            'total_outflow_artists' => $totalOutflowArtists,
            'total_outflow_bookers' => $totalOutflowBookers,
            'net_cashflow' => $netCashflow,
        ];
    }

    /**
     * Gera uma lista de transações cronológicas para a tabela de Fluxo de Caixa.
     *
     * @return Collection
     */
    public function getCashflowTableData(): Collection
    {
        // 1. Entradas (Pagamentos de Clientes)
        $inflows = Payment::whereNotNull('confirmed_at')
            ->whereBetween('received_date_actual', [$this->startDate, $this->endDate])
            ->whereHas('gig') // <<-- CORREÇÃO: Garante que a Gig relacionada não foi deletada
            ->with('gig.artist')
            ->get()
            ->map(function ($payment) {
                return [
                    'date' => Carbon::parse($payment->received_date_actual),
                    'type' => 'Entrada',
                    'description' => "Recebimento: " . ($payment->description ?: "Gig #{$payment->gig_id}"),
                    'gig_id' => $payment->gig_id,
                    'artist_name' => $payment->gig->artist->name ?? 'N/A',
                    'value' => (float)$payment->received_value_actual,
                ];
            });

        // 2. Saídas (Despesas)
        $outflowExpenses = GigCost::where('is_confirmed', true)
            ->whereBetween('confirmed_at', [$this->startDate, $this->endDate])
            ->whereHas('gig') // <<-- CORREÇÃO: Garante que a Gig relacionada não foi deletada
            ->with(['gig.artist', 'costCenter'])
            ->get()
            ->map(function ($cost) {
                return [
                    'date' => Carbon::parse($cost->confirmed_at),
                    'type' => 'Saída',
                    'description' => "Despesa ({$cost->costCenter->name}): " . ($cost->description ?: "Gig #{$cost->gig_id}"),
                    'gig_id' => $cost->gig_id,
                    'artist_name' => $cost->gig->artist->name ?? 'N/A',
                    'value' => -(float)$cost->value, // Negativo para indicar saída
                ];
            });

        // 3. Saídas (Acertos com Artistas)
        $outflowArtists = Settlement::whereNotNull('artist_payment_paid_at')
            ->whereBetween('artist_payment_paid_at', [$this->startDate, $this->endDate])
            ->whereHas('gig') // <<-- CORREÇÃO: Garante que a Gig relacionada não foi deletada
            ->with('gig.artist')
            ->get()
            ->map(function ($settlement) {
                return [
                    'date' => Carbon::parse($settlement->artist_payment_paid_at),
                    'type' => 'Saída',
                    'description' => "Pagamento Artista: {$settlement->gig->artist->name}",
                    'gig_id' => $settlement->gig_id,
                    'artist_name' => $settlement->gig->artist->name ?? 'N/A',
                    'value' => -(float)$settlement->artist_payment_value, // Negativo para indicar saída
                ];
            });
        
        // 4. Saídas (Acertos com Bookers)
        $outflowBookers = Settlement::whereNotNull('booker_commission_paid_at')
            ->whereBetween('booker_commission_paid_at', [$this->startDate, $this->endDate])
            ->whereHas('gig') // <<-- CORREÇÃO: Garante que a Gig relacionada não foi deletada
            ->with(['gig.artist', 'gig.booker'])
            ->get()
            ->map(function ($settlement) {
                return [
                    'date' => Carbon::parse($settlement->booker_commission_paid_at),
                    'type' => 'Saída',
                    'description' => "Pagamento Booker: {$settlement->gig->booker->name}",
                    'gig_id' => $settlement->gig_id,
                    'artist_name' => $settlement->gig->artist->name ?? 'N/A',
                    'value' => -(float)$settlement->booker_commission_value_paid, // Negativo para indicar saída
                ];
            });

        // 5. Junta tudo e ordena por data
        return $inflows->concat($outflowExpenses)->concat($outflowArtists)->concat($outflowBookers)->sortBy('date');
    }

    public function getCommissionsSummary(): array
    {
        $gigs = Gig::with(['booker'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get();

        $totalCommissions = 0;
        $eventsWithCommissionsCount = 0; // <<-- Variável para a contagem

        foreach ($gigs as $gig) {
            try {
                // Usamos o GigFinancialCalculatorService para consistência
                $commission = $this->calculator->calculateBookerCommissionBrl($gig);

                if ($commission > 0) { // Se a comissão calculada for maior que zero
                    $totalCommissions += $commission;
                    $eventsWithCommissionsCount++; // <<-- Incrementa o contador
                }
            } catch (\Exception $e) {
                Log::error("Erro ao calcular resumo de comissões para Gig ID {$gig->id}: " . $e->getMessage());
            }
        }

        return [
            'total_commissions' => $totalCommissions,
            'events_with_commissions' => $eventsWithCommissionsCount, // <<-- Retorna a contagem correta
            // Outras métricas que você queira adicionar...
        ];
    }

    public function getCommissionsTableData(): Collection
    {
        $gigs = Gig::with(['booker', 'artist'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get();

        return $gigs->map(function ($gig) {
            try {
                $bookerCommission = $this->calculator->calculateBookerCommissionBrl($gig);
                $revenue = $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
                $percentage = $revenue > 0 ? ($bookerCommission / $revenue) * 100 : 0;

                return [
                    'contract_number' => $gig->contract_number ?? 'N/A',
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'booker' => $gig->booker->name ?? 'N/A',
                    'artist' => $gig->artist->name ?? 'N/A',
                    'commission' => $bookerCommission,
                    'percentage' => $percentage,
                ];
            } catch (\Exception $e) {
                Log::error("Erro ao mapear dados de comissões para Gig ID {$gig->id}: " . $e->getMessage());
                return [
                    'contract_number' => 'Erro',
                    'gig_date' => 'N/A',
                    'booker' => 'N/A',
                    'artist' => 'N/A',
                    'commission' => 0,
                    'percentage' => 0,
                ];
            }
        });
    }

    public function getExpensesTableData(): Collection
    {
        $expenses = GigCost::with(['gig'])
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->whereHas('gig', fn($q) => $q->where('booker_id', $this->filters['booker_id'])))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->whereHas('gig', fn($q) => $q->where('artist_id', $this->filters['artist_id'])))
            ->get()
            ->filter(function ($expense) {
                return !is_null($expense->gig);
            })
            ->groupBy(function ($cost) {
                return $cost->cost_center_id ? __('cost_centers.' . $cost->costCenter->name) : 'Sem Centro de Custo';
            });

        return $expenses->map(function ($group, $costCenterName) {
            return [
                'cost_center_name' => $costCenterName,
                'total_brl' => $group->sum('value_brl'),
                'expenses' => $group->map(function ($expense) {
                    try {
                        return [
                            'gig_contract_number' => $expense->gig->contract_number ?? 'N/A',
                            'description' => $expense->description,
                            'expense_date' => $expense->expense_date->format('d/m/Y'),
                            'value_brl' => $expense->value_brl,
                            'currency' => $expense->currency ?? 'BRL',
                        ];
                    } catch (\Exception $e) {
                        Log::error("Erro ao mapear despesa ID {$expense->id}: " . $e->getMessage());
                        return [
                            'gig_contract_number' => 'Erro',
                            'description' => 'N/A',
                            'expense_date' => 'N/A',
                            'value_brl' => 0,
                            'currency' => 'BRL',
                        ];
                    }
                }),
            ];
        });
    }

    public function getFinancialReportData(): array
    {
        $gigs = Gig::with(['payments', 'costs', 'booker', 'artist'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get();

        $totalRevenue = 0;
        $totalAgencyCommissions = 0;
        $totalBookerCommissions = 0;
        $totalExpenses = 0;
        $eventsByArtist = [];
        $revenueByBooker = [];

        foreach ($gigs as $gig) {
            try {
                // Valor do contrato
                $contractValue = $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
                $totalRevenue += $contractValue;

                // Soma das despesas
                $expenses = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                $totalExpenses += $expenses;

                // Cachê bruto do artista
                $artistGrossCache = $contractValue - $expenses;

                // Comissão da agência (ex.: 20% do cachê bruto)
                $agencyCommission = $artistGrossCache * 0.2;
                $totalAgencyCommissions += $agencyCommission;

                // Comissão do booker (ex.: 5% do cachê bruto)
                $bookerCommission = $artistGrossCache * 0.05;
                $totalBookerCommissions += $bookerCommission;

                // Cachê líquido do artista
                $artistNetCache = $artistGrossCache - $agencyCommission;

                // Agrupar eventos por artista
                $artistName = $gig->artist->name ?? 'N/A';
                $eventsByArtist[$artistName][] = [
                    'date' => $gig->gig_date->format('d/m/Y'),
                    'location' => $gig->venue ?? 'N/A',
                    'contract_value' => $contractValue,
                    'agency_commission' => $agencyCommission,
                    'booker_commission' => $bookerCommission,
                    'net_cache' => $artistNetCache,
                    'event_count' => 1,
                ];

                // Agrupar faturamento por booker
                $bookerName = $gig->booker->name ?? 'N/A';
                $revenueByBooker[$bookerName][] = $contractValue;
            } catch (\Exception $e) {
                Log::error("Erro ao processar Gig ID {$gig->id}: " . $e->getMessage());
            }
        }

        $revenueByBookerSummary = [];
        foreach ($revenueByBooker as $booker => $revenues) {
            $revenueByBookerSummary[$booker] = array_sum($revenues);
        }

        $operationalExpenses = GigCost::whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->get()
            ->groupBy(function ($cost) {
                return $cost->cost_center_id ? $cost->costCenter->name : 'Sem Centro de Custo';
            })
            ->map(function ($group, $category) {
                return [
                    'category' => $category,
                    'total' => $group->sum('value_brl'),
                    'details' => $group->map(function ($expense) {
                        return [
                            'description' => $expense->description,
                            'value' => $expense->value_brl,
                        ];
                    }),
                ];
            });

        $totalOperationalExpenses = $operationalExpenses->sum('total');
        $netRevenue = $totalRevenue - $totalOperationalExpenses;
        $operationalResult = $netRevenue - $totalAgencyCommissions;

        return [
            'total_revenue' => $totalRevenue,
            'total_agency_commissions' => $totalAgencyCommissions,
            'total_booker_commissions' => $totalBookerCommissions,
            'total_events' => $gigs->count(),
            'events_by_artist' => $eventsByArtist,
            'revenue_by_booker' => $revenueByBookerSummary,
            'operational_expenses' => $operationalExpenses,
            'total_operational_expenses' => $totalOperationalExpenses,
            'net_revenue' => $netRevenue,
            'operational_result' => $operationalResult,
        ];
    }

    /**
     * Obtém os dados detalhados para a tabela de Visão Geral de Performance.
     * Retorna tanto os dados da tabela quanto os totais para o rodapé.
     *
     * @return array
     */
    public function getDetailedPerformanceData(): array
    {
        // 1. Aplica os filtros e busca as Gigs com seus relacionamentos
        $gigs = $this->applyFilters(Gig::query())
            ->with(['artist', 'booker']) // Eager load para performance
            ->get();

        $tableData = new Collection();
        $totals = [
            'cache_bruto_brl' => 0,
            'total_despesas_confirmadas_brl' => 0,
            'cache_liquido_base_brl' => 0,
            'repasse_estimado_artista_brl' => 0,
            'comissao_agencia_brl' => 0,
            'comissao_booker_brl' => 0,
            'comissao_agencia_liquida_brl' => 0,
        ];

        // 2. Itera sobre cada Gig e usa o GigFinancialCalculatorService para obter os valores corretos
        foreach ($gigs as $gig) {
            // Cada um desses métodos já tem sua própria lógica de cálculo correta
            $cacheBrutoBrl = $this->calculator->calculateGrossCashBrl($gig);
            $totalDespesasConfirmadasBrl = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
            $repasseEstimadoArtistaBrl = $this->calculator->calculateArtistNetPayoutBrl($gig);
            $comissaoAgenciaBrl = $this->calculator->calculateAgencyGrossCommissionBrl($gig);
            $comissaoBookerBrl = $this->calculator->calculateBookerCommissionBrl($gig);
            $comissaoAgenciaLiquidaBrl = $this->calculator->calculateAgencyNetCommissionBrl($gig);

            $tableData->push([
                'gig_date' => $gig->gig_date->format('d/m/Y'),
                'artist_name' => $gig->artist->name ?? 'N/A',
                'booker_name' => $gig->booker->name ?? 'N/A',
                'location_event_details' => $gig->location_event_details,
                'cache_bruto_original' => "{$gig->currency} " . number_format($gig->cache_value, 2, ',', '.'),
                'cache_bruto_brl' => $gig->cache_value_brl,
                'total_despesas_confirmadas_brl' => $totalDespesasConfirmadasBrl,
                'cache_liquido_base_brl' => $cacheBrutoBrl, // Este é o nosso "Cachê Bruto" (pós-despesas)
                'repasse_estimado_artista_brl' => $repasseEstimadoArtistaBrl,
                'comissao_agencia_brl' => $comissaoAgenciaBrl,
                'comissao_booker_brl' => $comissaoBookerBrl,
                'comissao_agencia_liquida_brl' => $comissaoAgenciaLiquidaBrl,
                'contract_status' => $gig->contract_status,
                'payment_status' => $gig->payment_status,
            ]);

            // 3. Soma os totais
            $totals['cache_bruto_brl'] += $gig->cache_value_brl;
            $totals['total_despesas_confirmadas_brl'] += $totalDespesasConfirmadasBrl;
            $totals['cache_liquido_base_brl'] += $cacheBrutoBrl;
            $totals['repasse_estimado_artista_brl'] += $repasseEstimadoArtistaBrl;
            $totals['comissao_agencia_brl'] += $comissaoAgenciaBrl;
            $totals['comissao_booker_brl'] += $comissaoBookerBrl;
            $totals['comissao_agencia_liquida_brl'] += $comissaoAgenciaLiquidaBrl;
        }

        return [
            'tableData' => $tableData,
            'totals' => $totals
        ];
    }

    /**
     * Obtém os dados para a análise de rentabilidade, agrupados por mês.
     *
     * @return array Contendo 'tableData', 'totals', e 'chartData'.
     */
    public function getProfitabilityAnalysisData(): array
    {
        // 1. Busca as Gigs com os filtros aplicados
        $gigs = $this->applyFilters(Gig::query()->whereNull('deleted_at'))
            ->with(['artist', 'booker']) // Eager load
            ->get();

        // 2. Agrupa as Gigs por Mês/Ano (formato 'YYYY-MM')
        $gigsByMonth = $gigs->groupBy(function ($gig) {
            return Carbon::parse($gig->gig_date)->format('Y-m');
        })->sortKeys();

        $tableData = new Collection();
        $chartData = [
            'labels' => [],
            'netAgencyCommission' => [],
            'grossMarginPercentage' => [],
            'commissionByBooker' => ['labels' => [], 'data' => []],
        ];

        // 3. Itera sobre cada grupo mensal para calcular as métricas
        foreach ($gigsByMonth as $monthYearKey => $monthlyGigs) {
            $totalCacheLiquidoBase = 0;
            $totalRepasseArtista = 0;
            $totalComissaoAgencia = 0;
            $totalComissaoBooker = 0;
            $totalComissaoAgenciaLiquida = 0;

            foreach ($monthlyGigs as $gig) {
                // Reutiliza nosso service central para garantir consistência
                $totalCacheLiquidoBase += $this->calculator->calculateGrossCashBrl($gig);
                $totalRepasseArtista += $this->calculator->calculateArtistNetPayoutBrl($gig);
                $totalComissaoAgencia += $this->calculator->calculateAgencyGrossCommissionBrl($gig);
                $totalComissaoBooker += $this->calculator->calculateBookerCommissionBrl($gig);
                $totalComissaoAgenciaLiquida += $this->calculator->calculateAgencyNetCommissionBrl($gig);
            }

            // Calcula a margem bruta da agência para o mês
            $margemBrutaAgencia = ($totalCacheLiquidoBase > 0)
                ? ($totalComissaoAgenciaLiquida / $totalCacheLiquidoBase) * 100
                : 0;

            $carbonMonth = Carbon::createFromFormat('Y-m', $monthYearKey);
            
                // Adiciona os dados agregados do mês à coleção da tabela
            $tableData->push([
                'month_year_key' => $monthYearKey,
                'month_label' => $carbonMonth->translatedFormat('F/Y'),
                'num_gigs' => $monthlyGigs->count(),
                'total_cache_liquido_base' => $totalCacheLiquidoBase,
                'total_repasse_artista' => $totalRepasseArtista,
                'total_comissao_agencia' => $totalComissaoAgencia,
                'total_comissao_booker' => $totalComissaoBooker,
                'total_comissao_agencia_liquida' => $totalComissaoAgenciaLiquida,
                'margem_bruta_agencia' => $margemBrutaAgencia,
            ]);

            // Prepara os dados para os gráficos
            $chartData['labels'][] = $carbonMonth->translatedFormat('M/y');
            $chartData['netAgencyCommission'][] = round($totalComissaoAgenciaLiquida, 2);
            $chartData['grossMarginPercentage'][] = round($margemBrutaAgencia, 2);
        }

        // Prepara os dados para o gráfico comparativo por booker
        $commissionByBooker = $gigs
            ->whereNotNull('booker_id') // Considera apenas gigs com booker
            ->groupBy(function($gig) {
                return $gig->booker->name ?? 'Booker Desconhecido'; // Agrupa pelo nome do booker
            })
            ->map(function ($bookerGigs) {
                return $bookerGigs->sum(function ($gig) {
                    // Usa a comissão líquida da agência, mas podemos mudar para comissão do booker se o gráfico for específico dele
                    return $this->calculator->calculateAgencyNetCommissionBrl($gig);
                });
            })
            // ***** ALTERAÇÃO AQUI para ORDENAR *****
            ->sortByDesc(function ($commission) { // Ordena pela comissão em ordem decrescente
                return $commission;
            });

        // Adiciona "Agência Direta" (Gigs sem booker) se houver
        $directAgencyCommission = $gigs
            ->whereNull('booker_id')
            ->sum(function ($gig) {
                return $this->calculator->calculateAgencyNetCommissionBrl($gig);
            });

        if ($directAgencyCommission > 0 || $commissionByBooker->isEmpty()) { // Adiciona se houver comissão direta ou se não houver nenhum booker
            // Para garantir a ordem, podemos adicionar e reordenar se necessário,
            // ou decidir onde "Agência Direta" deve aparecer (ex: no final se for pequena).
            // Por simplicidade, se a ordenação é puramente por valor, e "Agência Direta" for um valor,
            // ela será posicionada corretamente pelo sortByDesc.
            // Se quisermos um tratamento especial, a lógica de inserção precisa ser mais cuidadosa.
            // Vamos adicionar e deixar o sortByDesc tratar.
            if($directAgencyCommission > 0) { // Só adiciona se tiver valor
                 $commissionByBooker->put('Agência Direta', $directAgencyCommission);
                 // Re-ordenar APÓS adicionar "Agência Direta" para garantir que ela entre na ordenação correta
                 $commissionByBooker = $commissionByBooker->sortByDesc(function ($commission) {
                    return $commission;
                 });
            }
        }
        // ***** FIM DA ALTERAÇÃO *****

        $chartData['commissionByBooker'] = [
            'labels' => $commissionByBooker->keys()->all(),
            'data' => $commissionByBooker->values()->all(),
        ];

        return [
            'tableData' => $tableData,
            'chartData' => $chartData,
        ];
    }

    /**
     * Obtém uma lista paginada de despesas detalhadas com base em filtros avançados.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getDetailedExpenses()
    {
        // Inicia a query no modelo GigCost
        $query = GigCost::query()
            // Carrega os relacionamentos necessários para evitar N+1 queries
            ->with(['gig.artist', 'gig.booker', 'costCenter', 'confirmer'])
            ->latest('expense_date'); // Ordena pelas mais recentes por padrão

        // Aplica os filtros gerais (do formulário principal de relatórios)
        // Filtrando despesas de gigs de um artista específico
        if (!empty($this->filters['artist_id'])) {
            $query->whereHas('gig', function ($q) {
                $q->where('artist_id', $this->filters['artist_id']);
            });
        }

        // Filtrando despesas de gigs de um booker específico
        if (!empty($this->filters['booker_id'])) {
            $query->whereHas('gig', function ($q) {
                $q->where('booker_id', $this->filters['booker_id']);
            });
        }

        // Aplica filtros específicos da aba de Despesas (que virão do request)
        // Filtro por Centro de Custo
        if (!empty($this->filters['cost_center_id'])) {
            $query->where('cost_center_id', $this->filters['cost_center_id']);
        }

        // Filtro por Status (Confirmada/Pendente)
        if (isset($this->filters['status']) && $this->filters['status'] !== '') {
            $query->where('is_confirmed', (bool)$this->filters['status']);
        }
        
        // Filtro por Período (baseado na data da despesa)
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('expense_date', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('expense_date', '<=', $this->filters['end_date']);
        }

        // Pagina o resultado
        return $query->paginate(25)->withQueryString();
    }

    /**
     * Obtém os dados de despesas agrupados por centro de custo,
     * respeitando os filtros principais já definidos no service.
     *
     * @return array Contendo 'groups', 'total_geral', 'total_confirmado', 'total_pendente'
     */
    public function getGroupedExpensesData(): array
    {
        // 1. Inicia a query com os relacionamentos necessários
        $query = GigCost::query()->with(['gig.artist', 'costCenter', 'confirmer']);

        // 2. Aplica os filtros principais (Período, Artista, Booker) que já estão na classe
        //    (Filtramos as despesas que pertencem às Gigs que correspondem aos filtros)
        $query->whereHas('gig', function ($gigQuery) {
            // Reutiliza a lógica de filtro do service para Gigs
            $this->applyFilters($gigQuery);
        });

        // 3. Busca a coleção de despesas filtradas
        $costs = $query->latest('expense_date')->get();

        // 4. Calcula os totais para os cards de resumo
        $totalGeral = $costs->sum('value'); // Assumindo BRL
        $totalConfirmado = $costs->where('is_confirmed', true)->sum('value');
        $totalPendente = $totalGeral - $totalConfirmado;

        // 5. Agrupa os resultados por Centro de Custo
        $groupedCosts = $costs->groupBy(function ($cost) {
            // Agrupa pelo NOME TRADUZIDO
            return $cost->costCenter ? __('cost_centers.' . $cost->costCenter->name) : 'Sem Centro de Custo';
        })->map(function ($costsInGroup, $translatedCostCenterName) { // A chave agora é o nome traduzido
            return [
                'cost_center_name' => $translatedCostCenterName, // Usa a chave diretamente
                'subtotal' => $costsInGroup->sum('value_brl'),
                'costs' => $costsInGroup,
            ];
        })->sortBy('cost_center_name');

        return [
            'groups' => $groupedCosts,
            'total_geral' => $totalGeral,
            'total_confirmado' => $totalConfirmado,
            'total_pendente' => $totalPendente,
        ];
    }

    /**
     * Obtém os dados de comissões de bookers, agrupados por booker.
     *
     * @return array Contendo 'groups' e dados para o 'summary'.
     */
    public function getGroupedCommissionsData(): array
    {
        $gigs = $this->applyFilters(Gig::query())
            ->whereNotNull('booker_id')
            ->with(['artist', 'booker'])
            ->get();

        // 2. Calcula os valores necessários para cada gig e filtra
        $gigsWithCommission = $gigs->map(function ($gig) {
            // Usa o service central para obter os valores e adicioná-los como propriedades temporárias ao objeto Gig
            $gig->calculated_booker_commission = $this->calculator->calculateBookerCommissionBrl($gig);
            $gig->calculated_gross_cash_brl = $this->calculator->calculateGrossCashBrl($gig); // <<-- PRÉ-CALCULA A BASE AQUI
            return $gig;
        })->filter(function ($gig) {
            return $gig->calculated_booker_commission > 0;
        });

        // 3. Calcula os totais para os cards de resumo
        $totalCommissions = $gigsWithCommission->sum('calculated_booker_commission');
        $eventsWithCommissionsCount = $gigsWithCommission->count();
        $totalCommissionBase = $gigsWithCommission->sum('calculated_gross_cash_brl');

        // 4. Agrupa os resultados por Nome do Booker
        $groupedByBooker = $gigsWithCommission->groupBy('booker.name')
            ->map(function ($bookerGigs, $bookerName) {
                return [
                    'booker_name' => $bookerName,
                    'gig_count' => $bookerGigs->count(),
                    'total_commission_base' => $bookerGigs->sum('calculated_gross_cash_brl'),
                    'total_commission_value' => $bookerGigs->sum('calculated_booker_commission'),
                    'gigs' => $bookerGigs, // A coleção de gigs agora contém a propriedade 'calculated_gross_cash_brl'
                ];
            })
            ->sortByDesc('total_commission_value');

        return [
            'summary' => [
                'total_commissions' => $totalCommissions,
                'events_with_commissions' => $eventsWithCommissionsCount,
                'total_commission_base' => $totalCommissionBase,
            ],
            'groups' => $groupedByBooker,
        ];
    }

    /**
     * Obtém os dados de rentabilidade por "venda" (Gig),
     * ordenados pela data do contrato ou, na sua ausência, pela data do evento.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSalesProfitabilityData(): \Illuminate\Support\Collection
    {
        // 1. Define a "data da venda" usando COALESCE e aplica os filtros
        $gigsQuery = $this->applyFilters(Gig::query()->whereNull('deleted_at'))
            ->select(
                'gigs.*', // Seleciona todas as colunas de gigs
                DB::raw('COALESCE(gigs.contract_date, gigs.gig_date) as sale_date') // 2. Cria a coluna virtual 'sale_date'
            )
            ->with('costs'); // Carrega o relacionamento de custos para evitar N+1

        // 3. Ordena pela "data da venda"
        $gigs = $gigsQuery->orderBy('sale_date', 'desc')->get();

        // 4. Mapeia os resultados para o formato final, calculando a rentabilidade
        return $gigs->map(function ($gig) {
            // Valor do contrato em BRL. Usamos o accessor que já lida com a conversão.
            $revenue = $gig->cache_value_brl ?? 0;
            
            // Soma das despesas confirmadas da Gig
            $totalCosts = $gig->costs->where('is_confirmed', true)->sum('value_brl');

            // Cálculo da rentabilidade e margem
            $profitability = $revenue - $totalCosts;
            $margin = ($revenue > 0) ? ($profitability / $revenue) * 100 : 0;

            return [
                'sale_date' => \Carbon\Carbon::parse($gig->sale_date)->format('d/m/Y'),
                'gig_id' => $gig->id,
                'gig_name' => $gig->artist->name . ' @ ' . Str::limit($gig->location_event_details, 30),
                'gig_contract_number' => $gig->contract_number,
                'revenue' => $revenue,
                'costs' => $totalCosts,
                'profitability' => $profitability,
                'margin' => $margin,
            ];
        });
    }

    /**
     * Obtém os dados detalhados para a Visão Geral, agrupados por artista.
     *
     * @return array
     */
    public function getOverviewData(): array
    {
        $gigs = $this->applyFilters(Gig::query()->whereNull('deleted_at'))
            ->with(['artist', 'booker', 'costs'])
            ->get();

        $dataByArtist = $gigs->groupBy('artist.name')
            ->sortBy(function ($gigs, $artistName) {
                return $artistName; // Ordena os grupos de artistas em ordem alfabética
            })
            ->map(function ($artistGigs, $artistName) {

            // Ordena as gigs dentro do grupo do artista
            $sortedGigs = $artistGigs->sortBy('gig_date');
            
            $subtotals = [
                'cache_bruto_brl' => 0, 'total_despesas_confirmadas_brl' => 0,
                'cache_liquido_base_brl' => 0, 'repasse_estimado_artista_brl' => 0,
                'comissao_agencia_brl' => 0, 'comissao_booker_brl' => 0,
                'comissao_agencia_liquida_brl' => 0,
            ];

            $gigsData = $sortedGigs->map(function ($gig) use (&$subtotals) {
                $cacheBrutoBrl = $gig->cache_value_brl ?? 0;
                $totalDespesasConfirmadasBrl = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                $cacheLiquidoBaseBrl = $this->calculator->calculateGrossCashBrl($gig);
                $repasseEstimadoArtistaBrl = $this->calculator->calculateArtistNetPayoutBrl($gig);
                $comissaoAgenciaBrl = $this->calculator->calculateAgencyGrossCommissionBrl($gig);
                $comissaoBookerBrl = $this->calculator->calculateBookerCommissionBrl($gig);
                $comissaoAgenciaLiquidaBrl = $this->calculator->calculateAgencyNetCommissionBrl($gig);

                // Soma aos subtotais
                $subtotals['cache_bruto_brl'] += $cacheBrutoBrl;
                $subtotals['total_despesas_confirmadas_brl'] += $totalDespesasConfirmadasBrl;
                $subtotals['cache_liquido_base_brl'] += $cacheLiquidoBaseBrl;
                $subtotals['repasse_estimado_artista_brl'] += $repasseEstimadoArtistaBrl;
                $subtotals['comissao_agencia_brl'] += $comissaoAgenciaBrl;
                $subtotals['comissao_booker_brl'] += $comissaoBookerBrl;
                $subtotals['comissao_agencia_liquida_brl'] += $comissaoAgenciaLiquidaBrl;
                
                return [
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'booker_name' => $gig->booker->name ?? 'N/A',
                    'location_event_details' => $gig->location_event_details,
                    'cache_bruto_original' => "{$gig->currency} " . number_format($gig->cache_value, 2, ',', '.'),
                    'cache_bruto_brl' => $cacheBrutoBrl,
                    'total_despesas_confirmadas_brl' => $totalDespesasConfirmadasBrl,
                    'cache_liquido_base_brl' => $cacheLiquidoBaseBrl,
                    'repasse_estimado_artista_brl' => $repasseEstimadoArtistaBrl,
                    'comissao_agencia_brl' => $comissaoAgenciaBrl,
                    'comissao_booker_brl' => $comissaoBookerBrl,
                    'comissao_agencia_liquida_brl' => $comissaoAgenciaLiquidaBrl,
                    // ***** CORREÇÃO: ADICIONANDO AS CHAVES FALTANTES *****
                    'contract_status' => $gig->contract_status,
                    'payment_status' => $gig->payment_status,
                ];
            });

            return [
                'artist_name' => $artistName,
                'gigs' => $gigsData,
                'subtotals' => $subtotals,
                'gig_count' => $artistGigs->count()
            ];
        });

        // Lógica de cálculo dos totais gerais (como antes)
        $grandTotals = [
            'cache_bruto_brl' => $dataByArtist->sum('subtotals.cache_bruto_brl'),
            'total_despesas_confirmadas_brl' => $dataByArtist->sum('subtotals.total_despesas_confirmadas_brl'),
            'cache_liquido_base_brl' => $dataByArtist->sum('subtotals.cache_liquido_base_brl'),
            'repasse_estimado_artista_brl' => $dataByArtist->sum('subtotals.repasse_estimado_artista_brl'),
            'comissao_agencia_brl' => $dataByArtist->sum('subtotals.comissao_agencia_brl'),
            'comissao_booker_brl' => $dataByArtist->sum('subtotals.comissao_booker_brl'),
            'comissao_agencia_liquida_brl' => $dataByArtist->sum('subtotals.comissao_agencia_liquida_brl'),
            'gig_count' => $dataByArtist->sum('gig_count'),
        ];
        
        return [
            'dataByArtist' => $dataByArtist,
            'grandTotals' => $grandTotals
        ];
    }

}