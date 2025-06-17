<?php
namespace App\Services;

use App\Models\Payment;
use App\Models\Gig;
use App\Models\GigCost;
use Carbon\Carbon;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service para calcular e agregar as projeções financeiras da agência.
 */
class FinancialProjectionService
{
    /** @var Carbon A data de início do período de projeção. */
    protected $startDate;

    /** @var Carbon A data final do período de projeção. */
    protected $endDate;

    /** @var GigFinancialCalculatorService Instância do service de cálculo financeiro. */
    protected $calculatorService;

    public function __construct(GigFinancialCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
        $this->setPeriod('30_days');
    }

    /**
     * Define o período da projeção a partir de hoje.
     *
     * @param string $period Identificador do período (ex: '30_days').
     */
    public function setPeriod(string $period): void
    {
        $this->startDate = Carbon::today()->startOfDay();
        switch ($period) {
            case '60_days':
                $this->endDate = Carbon::today()->addDays(60)->endOfDay();
                break;
            case '90_days':
                $this->endDate = Carbon::today()->addDays(90)->endOfDay();
                break;
            case 'next_quarter':
                $this->endDate = Carbon::today()->addMonths(3)->endOfDay();
                break;
            case '30_days':
            default:
                $this->endDate = Carbon::today()->addDays(30)->endOfDay();
                break;
        }
    }

    /**
     * Calcula o total de contas a receber de clientes.
     * Soma o valor de TODAS as parcelas não confirmadas (vencidas ou a vencer).
     *
     * @return float O valor total a receber em BRL.
     */
    public function getAccountsReceivable(): float
    {
        // Pega todos os pagamentos não confirmados, independente da data de vencimento.
        $payments = Payment::whereNull('confirmed_at')
            ->whereHas('gig') // Garante que a gig não foi deletada
            ->get();

        // O accessor 'due_value_brl' no modelo Payment garante a conversão correta.
        return (float) $payments->sum('due_value_brl');
    }

    /**
     * Retorna a lista detalhada de pagamentos pendentes de clientes.
     * Inclui tanto os vencidos quanto os que estão no período de projeção.
     *
     * @return Collection
     */
    public function getUpcomingClientPayments(): Collection
    {
        return Payment::whereNull('confirmed_at')
            ->whereHas('gig') // Garante que a gig não foi deletada
            ->where(function ($query) {
                // Pega pagamentos vencidos OU que vencerão no período da projeção
                $query->where('due_date', '<', $this->startDate) // Vencidos
                      ->orWhereBetween('due_date', [$this->startDate, $this->endDate]); // A vencer no período
            })
            ->with('gig')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Calcula as contas a pagar para Artistas no período de projeção.
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

    /**
     * Retorna a lista de Gigs com pagamentos pendentes a artistas ou bookers.
     *
     * @param string $type 'artists' ou 'bookers'
     * @return Collection
     */
    public function getUpcomingInternalPayments(string $type): Collection
    {
        $statusColumn = ($type === 'artists') ? 'artist_payment_status' : 'booker_payment_status';

        return Gig::where($statusColumn, 'pendente')
            ->whereBetween('gig_date', [$this->startDate, $this->endDate])
            ->with(['artist', 'booker']) // Eager load para performance
            ->orderBy('gig_date')
            ->get();
    }
}