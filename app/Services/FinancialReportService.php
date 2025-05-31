<?php

namespace App\Services;

use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Services\GigFinancialCalculatorService;

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
        $gigs = Gig::with(['costs', 'payments'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get();

        $totalRevenue = 0;
        $totalCommissions = 0;
        $totalExpenses = 0;
        $netProfit = 0;

        foreach ($gigs as $gig) {
            try {
                $revenue = $gig->payments
                    ->whereNotNull('confirmed_at')
                    ->sum('due_value_brl');
                $totalRevenue += $revenue;

                $bookerCommission = $this->calculator->calculateBookerCommissionBrl($gig);
                $totalCommissions += $bookerCommission;

                $expenses = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                $totalExpenses += $expenses;

                $netProfit += ($revenue - ($bookerCommission + $expenses));
            } catch (\Exception $e) {
                \Log::error("Erro ao calcular resumo para Gig ID {$gig->id}: " . $e->getMessage());
            }
        }

        return [
            'total_revenue' => $totalRevenue,
            'total_commissions' => $totalCommissions,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
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
                \Log::error("Erro ao mapear dados da Gig ID {$gig->id}: " . $e->getMessage());
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
                \Log::error("Erro ao calcular resumo de rentabilidade para Gig ID {$gig->id}: " . $e->getMessage());
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
            $profit = $revenue - ($costs + $commission);
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            return [
                'contract_number' => $gig->contract_number ?? 'N/A',
                'gig_date' => $gig->gig_date->format('d/m/Y'),
                'artist' => $gig->artist->name ?? 'N/A',
                'booker' => $gig->booker->name ?? 'N/A',
                'revenue' => $revenue,
                'costs' => $costs,
                'profit' => $profit,
                'margin' => $margin,
            ];
        } catch (\Exception $e) {
            \Log::error("Erro ao mapear dados de rentabilidade para Gig ID {$gig->id}: " . $e->getMessage());
            return [
                'contract_number' => 'Erro',
                'gig_date' => 'N/A',
                'artist' => 'N/A',
                'booker' => 'N/A',
                'revenue' => 0,
                'costs' => 0,
                'profit' => 0,
                'margin' => 0,
            ];
        }
    });
}

    public function getCashflowSummary(): array
    {
        $gigs = Gig::with(['payments', 'costs'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get();

        $totalInflow = 0;
        $totalOutflow = 0;

        foreach ($gigs as $gig) {
            try {
                $revenue = $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
                $costs = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                $totalInflow += $revenue;
                $totalOutflow += $costs;
            } catch (\Exception $e) {
                \Log::error("Erro ao calcular resumo de fluxo de caixa para Gig ID {$gig->id}: " . $e->getMessage());
            }
        }

        return [
            'total_inflow' => $totalInflow,
            'total_outflow' => $totalOutflow,
            'net_cashflow' => $totalInflow - $totalOutflow,
        ];
    }

    public function getCashflowTableData(): Collection
{
    return Gig::with(['payments', 'costs'])
        ->whereBetween('gig_date', [$this->startDate, $this->endDate])
        ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
        ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
        ->get()
        ->map(function ($gig) {
            try {
                $revenue = $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
                $costs = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                return [
                    'contract_number' => $gig->contract_number ?? 'N/A',
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'revenue' => $revenue,
                    'costs' => $costs,
                    'net_cashflow' => $revenue - $costs,
                ];
            } catch (\Exception $e) {
                \Log::error("Erro ao mapear dados de fluxo de caixa para Gig ID {$gig->id}: " . $e->getMessage());
                return [
                    'contract_number' => 'Erro',
                    'gig_date' => 'N/A',
                    'revenue' => 0,
                    'costs' => 0,
                    'net_cashflow' => 0,
                ];
            }
        });
}

    public function getCommissionsSummary(): array
    {
        $gigs = Gig::with(['booker'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get();

        $totalCommissions = 0;
        $bookers = [];

        foreach ($gigs as $gig) {
            try {
                $commission = $this->calculator->calculateBookerCommissionBrl($gig);
                $totalCommissions += $commission;
                $bookerId = $gig->booker->id ?? 'unknown';
                $bookers[$bookerId] = true;
            } catch (\Exception $e) {
                \Log::error("Erro ao calcular resumo de comissões para Gig ID {$gig->id}: " . $e->getMessage());
            }
        }

        $totalBookers = count($bookers);
        $averagePerBooker = $totalBookers > 0 ? $totalCommissions / $totalBookers : 0;

        return [
            'total_commissions' => $totalCommissions,
            'total_bookers' => $totalBookers,
            'average_per_booker' => $averagePerBooker,
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
                \Log::error("Erro ao mapear dados de comissões para Gig ID {$gig->id}: " . $e->getMessage());
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
                return $cost->cost_center_id ? $cost->costCenter->name : 'Sem Centro de Custo';
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
                        \Log::error("Erro ao mapear despesa ID {$expense->id}: " . $e->getMessage());
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
                \Log::error("Erro ao processar Gig ID {$gig->id}: " . $e->getMessage());
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
}