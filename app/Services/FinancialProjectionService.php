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

    /**
     * Calcula as contas a pagar para Artistas no período.
     * Soma o valor final da NF de cada Gig pendente de pagamento.
     *
     * @return float
     */
    public function getAccountsPayableArtists(): float
    {
        // Busca Gigs com pagamento ao artista pendente e data no período de projeção
        $gigs = Gig::where('artist_payment_status', 'pendente')
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->get();

        $total = 0;
        foreach ($gigs as $gig) {
            // ** A CORREÇÃO ESTÁ AQUI **
            // Usamos `calculateArtistInvoiceValueBrl` que já inclui
            // o cachê líquido do artista MAIS as despesas reembolsáveis.
            $total += $this->calculatorService->calculateArtistInvoiceValueBrl($gig);
        }

        return (float) max(0, $total);
    }

    /**
     * Calcula as contas a pagar para Bookers (comissões) no período.
     *
     * @return float
     */
    public function getAccountsPayableBookers(): float
    {
        $gigs = Gig::where('booker_payment_status', 'pendente')
            ->whereNotNull('booker_id') // Garante que só pegamos gigs com booker
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->get();
        
        $total = 0;
        foreach ($gigs as $gig) {
            $bookerCommission = $this->calculatorService->calculateBookerCommissionBrl($gig);
            if ($bookerCommission > 0) {
                $total += $bookerCommission;
            }
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