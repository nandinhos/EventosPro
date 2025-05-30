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
            $netProfit = $revenue - ($costs + $commission);
            $profitMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0; // Margem de lucro em %

            return [
                'contract_number' => $gig->contract_number ?? 'N/A',
                'gig_date' => $gig->gig_date->format('d/m/Y'),
                'artist' => $gig->artist->name ?? 'N/A',
                'booker' => $gig->booker->name ?? 'N/A',
                'revenue' => $revenue,
                'net_profit' => $netProfit,
                'profit_margin' => $profitMargin, // Nova métrica para Rentabilidade
            ];
        } catch (\Exception $e) {
            \Log::error("Erro ao mapear dados de rentabilidade para Gig ID {$gig->id}: " . $e->getMessage());
            return [
                'contract_number' => 'Erro',
                'gig_date' => 'N/A',
                'artist' => 'N/A',
                'booker' => 'N/A',
                'revenue' => 0,
                'net_profit' => 0,
                'profit_margin' => 0,
            ];
        }
    });
}

    public function getCashflowTableData(): Collection
    {
        return Gig::with(['payments', 'costs'])
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
            ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
            ->get()
            ->map(function ($gig) {
                $revenue = $gig->payments->whereNotNull('confirmed_at')->sum('due_value_brl');
                $costs = $this->calculator->calculateTotalConfirmedExpensesBrl($gig);
                return [
                    'contract_number' => $gig->contract_number,
                    'gig_date' => $gig->gig_date->format('d/m/Y'),
                    'revenue' => $revenue,
                    'costs' => $costs,
                    'net_cashflow' => $revenue - $costs,
                ];
            });
    }

    public function getCommissionsTableData(): Collection
{
    $gigs = Gig::with(['booker'])
        ->whereBetween('gig_date', [$this->startDate, $this->endDate])
        ->when(isset($this->filters['booker_id']), fn($q) => $q->where('booker_id', $this->filters['booker_id']))
        ->when(isset($this->filters['artist_id']), fn($q) => $q->where('artist_id', $this->filters['artist_id']))
        ->get();

    return $gigs->map(function ($gig) {
        try {
            $bookerCommission = $this->calculator->calculateBookerCommissionBrl($gig);
            return [
                'contract_number' => $gig->contract_number ?? 'N/A',
                'gig_date' => $gig->gig_date->format('d/m/Y'),
                'booker' => $gig->booker->name ?? 'N/A',
                'commission' => $bookerCommission,
            ];
        } catch (\Exception $e) {
            \Log::error("Erro ao mapear dados de comissões para Gig ID {$gig->id}: " . $e->getMessage());
            return [
                'contract_number' => 'Erro',
                'gig_date' => 'N/A',
                'booker' => 'N/A',
                'commission' => 0,
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
}