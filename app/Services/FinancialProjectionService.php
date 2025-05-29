<?php
namespace App\Services;

use App\Models\Payment;
use App\Models\Gig;
use App\Models\GigCost;
use Carbon\Carbon;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Support\Collection;

class FinancialProjectionService
{
    protected $startDate;
    protected $endDate;
    protected $calculatorService;

    public function __construct(GigFinancialCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
        $this->setPeriod('30_days');
    }

    public function setPeriod($period)
    {
        $this->startDate = Carbon::today();
        switch ($period) {
            case '30_days':
                $this->endDate = Carbon::today()->addDays(30);
                break;
            case '60_days':
                $this->endDate = Carbon::today()->addDays(60);
                break;
            case '90_days':
                $this->endDate = Carbon::today()->addDays(90);
                break;
            case 'next_quarter':
                $this->endDate = Carbon::today()->addQuarter();
                break;
            default:
                $this->endDate = Carbon::today()->addDays(30);
        }
    }

    // Contas a Receber (Clientes)
    public function getAccountsReceivable()
    {
        $payments = Payment::whereNull('confirmed_at')
            ->whereBetween('due_date', [$this->startDate, $this->endDate])
            ->get();

        return $payments->sum('due_value_brl');
    }

    // Contas a Pagar (Artistas)
    public function getAccountsPayableArtists()
    {
        $gigs = Gig::where('artist_payment_status', 'pendente')
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->get();

        $total = 0;
        foreach ($gigs as $gig) {
            $total += $this->calculatorService->calculateArtistNetPayoutBrl($gig);
        }

        return (float) max(0, $total);
    }

    // Contas a Pagar (Bookers)
    public function getAccountsPayableBookers()
    {
        $gigs = Gig::where('booker_payment_status', 'pendente')
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->get();

        $total = 0;
        foreach ($gigs as $gig) {
            $total += $this->calculatorService->calculateBookerCommissionBrl($gig);
        }

        return (float) max(0, $total);
    }

    // Contas a Pagar (Despesas Previstas)
    public function getAccountsPayableExpenses()
    {
        $costs = GigCost::where('is_confirmed', false)
            ->where(function ($query) {
                $query->whereBetween('expense_date', [$this->startDate, $this->endDate])
                      ->orWhereNull('expense_date')
                      ->whereHas('gig', function ($subQuery) {
                          $subQuery->whereBetween('gig_date', [$this->startDate, $this->endDate]);
                      });
            })
            ->get();

        return $costs->sum('value_brl');
    }

    // Despesas Previstas Agrupadas por Centro de Custo com Detalhes por Gig
    public function getProjectedExpensesByCostCenter(): Collection
    {
        $costs = GigCost::where('is_confirmed', false)
            ->where(function ($query) {
                $query->whereBetween('expense_date', [$this->startDate, $this->endDate])
                      ->orWhereNull('expense_date')
                      ->whereHas('gig', function ($subQuery) {
                          $subQuery->whereBetween('gig_date', [$this->startDate, $this->endDate]);
                      });
            })
            ->with(['costCenter', 'gig']) // Carrega relacionamentos
            ->get();

        // Agrupa por cost_center_id
        return $costs->groupBy('cost_center_id')->map(function ($group) {
            $costCenter = $group->first()->costCenter;
            $totalBrl = $group->sum('value_brl');

            // Detalhes de cada despesa
            $expenses = $group->map(function ($cost) {
                return [
                    'gig_contract_number' => $cost->gig->contract_number ?? 'N/A',
                    'description' => $cost->description,
                    'expense_date' => $cost->expense_date ?? $cost->gig->gig_date,
                    'value_brl' => (float) $cost->value_brl,
                    'currency' => strtoupper($cost->currency ?? 'BRL'),
                ];
            })->sortBy('expense_date'); // Ordena por data da despesa

            return [
                'cost_center_name' => $costCenter->name ?? 'Sem Centro de Custo',
                'total_brl' => $totalBrl,
                'expenses' => $expenses,
            ];
        })->sortBy('cost_center_name')->values();
    }

    // Fluxo de Caixa Projetado
    public function getProjectedCashFlow()
    {
        $receivable = $this->getAccountsReceivable();
        $payable = $this->getAccountsPayableArtists() + $this->getAccountsPayableBookers() + $this->getAccountsPayableExpenses();
        return (float) $receivable - $payable;
    }

    // Listagem de Próximos Pagamentos
    public function getUpcomingPayments($type = 'clients')
    {
        switch ($type) {
            case 'clients':
                return Payment::whereNull('confirmed_at')
                    ->whereBetween('due_date', [$this->startDate, $this->endDate])
                    ->orderBy('due_date')
                    ->get();
            case 'artists':
                return Gig::where('artist_payment_status', 'pendente')
                    ->whereBetween('gig_date', [$this->startDate, $this->endDate])
                    ->orderBy('gig_date')
                    ->get();
            case 'bookers':
                return Gig::where('booker_payment_status', 'pendente')
                    ->whereBetween('gig_date', [$this->startDate, $this->endDate])
                    ->orderBy('gig_date')
                    ->get();
        }
    }
}