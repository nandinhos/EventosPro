<?php
namespace App\Services;

use App\Models\Payment;
use App\Models\Gig;
use App\Models\GigCost;
use Carbon\Carbon;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * Define o período da projeção.
     *
     * @param string $period Identificador do período.
     */
    public function setPeriod(string $period): void
    {
        $today = Carbon::today()->startOfDay(); // Usar startOfDay para consistência
        $this->startDate = $today; // Projeção sempre começa de hoje

        switch ($period) {
            case '60_days':
                $this->endDate = $today->copy()->addDays(59)->endOfDay(); // 60 dias a partir de hoje
                break;
            case '90_days':
                $this->endDate = $today->copy()->addDays(89)->endOfDay(); // 90 dias a partir de hoje
                break;
            case 'next_semester':
                // Semestre corrente:
                // Se hoje está entre Jan-Jun, o semestre termina em 30 de Junho.
                // Se hoje está entre Jul-Dez, o semestre termina em 31 de Dezembro.
                // Próximo semestre: 6 meses a partir do final do semestre corrente.
                $endOfCurrentSemester = $today->month <= 6 ? $today->copy()->month(6)->endOfMonth() : $today->copy()->month(12)->endOfMonth();
                // Para a projeção, queremos *até o final* do próximo semestre
                $this->endDate = $endOfCurrentSemester->copy()->addMonths(6)->endOfMonth()->endOfDay();
                break;
            case 'next_year':
                // Projeção até o final do próximo ano civil
                $this->endDate = $today->copy()->addYear()->endOfYear()->endOfDay();
                break;
            case '30_days':
            default:
                $this->endDate = $today->copy()->addDays(29)->endOfDay(); // 30 dias a partir de hoje (hoje + 29 dias)
                break;
        }
        Log::info("[FinancialProjectionService] Período de projeção '{$period}' definido: De {$this->startDate->toDateString()} até {$this->endDate->toDateString()}");
    }

    /**
     * Calcula o total de contas a receber de clientes.
     * Soma o valor de TODAS as parcelas não confirmadas (vencidas ou a vencer).
     *
     * @return float O valor total a receber em BRL.
     */
    public function getAccountsReceivable(): float
    {
        // Contas a receber: todas as parcelas não confirmadas, independente da data de vencimento.
        // Se uma parcela venceu no passado e não foi confirmada, ela ainda é "a receber".
        $payments = Payment::whereNull('confirmed_at')
            ->whereHas('gig') // Garante que a gig não foi deletada
            ->get();
        return (float) $payments->sum('due_value_brl'); // Accessor já converte para BRL
    }

    /**
     * Retorna a lista detalhada de pagamentos pendentes de clientes.
     * Inclui tanto os vencidos quanto os que estão no período de projeção.
     *
     * @return Collection
     */
    public function getUpcomingClientPayments(): Collection
    {
        // Listagem de contas a receber: Vencidas + A vencer no período de projeção.
        // $this->startDate é hoje.
        return Payment::whereNull('confirmed_at')
            ->whereHas('gig')
            ->where(function ($query) {
                $query->where('due_date', '<', $this->startDate) // Vencidos até ontem
                      ->orWhereBetween('due_date', [$this->startDate, $this->endDate]); // A vencer no período
            })
            ->with(['gig' => function ($query) {
                $query->with('artist');
            }])
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Calcula as contas a pagar para Artistas no período de projeção.
     * **USA O VALOR FINAL DA NOTA FISCAL DO ARTISTA (CACHÊ LÍQUIDO + REEMBOLSOS)**
     *
     * @return float
     */
    public function getAccountsPayableArtists(): float
    {
        // Contas a pagar artistas:
        // 1. Gigs passadas com status de pagamento pendente.
        // 2. Gigs futuras (até $this->endDate) com status de pagamento pendente.
        $gigs = Gig::where('artist_payment_status', 'pendente')
            ->where(function ($query) {
                $query->where('gig_date', '<', $this->startDate) // Gigs passadas
                      ->orWhereBetween('gig_date', [$this->startDate, $this->endDate]); // Gigs no período de projeção
            })
            ->get();

        $total = 0;
        foreach ($gigs as $gig) {
            $artistPaymentValue = $this->calculatorService->calculateArtistInvoiceValueBrl($gig);
            Log::debug("[FinancialProjectionService] Pagar Artista: Gig ID: {$gig->id}, Data Gig: {$gig->gig_date->toDateString()}, Valor NF: {$artistPaymentValue}");
            $total += $artistPaymentValue;
        }
        Log::info("[FinancialProjectionService] Total Contas a Pagar Artistas (Passado Pendente + Projetado): {$total}");
        return (float) max(0, $total);
    }

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      /**
     * Calcula as contas a pagar para Bookers (comissões) no período.
     *
     * @return float
     */
    public function getAccountsPayableBookers(): float
    {
        // Contas a pagar bookers:
        // 1. Gigs passadas com status de pagamento pendente.
        // 2. Gigs futuras (até $this->endDate) com status de pagamento pendente.
        $gigs = Gig::where('booker_payment_status', 'pendente')
            ->whereNotNull('booker_id')
            ->where(function ($query) {
                $query->where('gig_date', '<', $this->startDate) // Gigs passadas
                      ->orWhereBetween('gig_date', [$this->startDate, $this->endDate]); // Gigs no período de projeção
            })
            ->get();
        
        $total = 0;
        foreach ($gigs as $gig) {
            $bookerCommission = $this->calculatorService->calculateBookerCommissionBrl($gig);
            Log::debug("[FinancialProjectionService] Pagar Booker: Gig ID: {$gig->id}, Data Gig: {$gig->gig_date->toDateString()}, Comissão: {$bookerCommission}");
            if ($bookerCommission > 0) {
                $total += $bookerCommission;
            }
        }
        Log::info("[FinancialProjectionService] Total Contas a Pagar Bookers (Passado Pendente + Projetado): {$total}");
        return (float) max(0, $total);
    }

    /**
     * Calcula as contas a pagar (Despesas Previstas).
     * Considera despesas não confirmadas de Gigs ATIVAS (não deletadas) que tenham data passada ou no período da projeção,
     * OU que não tenham data mas pertençam a uma Gig ATIVA passada ou no período da projeção.
     * @return float
     */
    public function getAccountsPayableExpenses(): float
    {
        $costs = GigCost::where('is_confirmed', false)
            // Garante que a Gig associada não foi deletada (soft delete)
            ->whereHas('gig', function ($gigQuery) { // Adiciona a verificação da Gig aqui
                $gigQuery->whereNull('deleted_at'); // <-- SÓ GIGS ATIVAS
            })
            ->where(function ($query) { // Lógica de data para as despesas
                // Condição 1: Despesa tem data E (essa data é passada OU está dentro do período de projeção futuro)
                $query->where(function($q1) {
                    $q1->whereNotNull('expense_date')
                       ->where('expense_date', '<=', $this->endDate);
                })
                // Condição 2: Despesa NÃO tem data, MAS sua Gig associada tem data (passada OU dentro do período de projeção futuro)
                      ->orWhere(function($q2){
                          $q2->whereNull('expense_date')
                             ->whereHas('gig', function ($gigSubQuery) { // whereHas já está aqui, apenas adicionamos a condição de data da gig
                                 $gigSubQuery->where('gig_date', '<=', $this->endDate);
                             });
                      });
            })
            ->with('gig')
            ->get();

        $totalExpenses = 0;
        foreach ($costs as $cost) {
            $totalExpenses += $cost->value_brl;
        }
        
        Log::info("[FinancialProjectionService] Total Contas a Pagar Despesas (getAccountsPayableExpenses) no período: {$totalExpenses}. Quantidade de custos: " . $costs->count());
        return (float) $totalExpenses;
    }

    /**
     * Obtém as despesas previstas agrupadas por centro de custo para o período.
     * A lógica de busca de custos deve ser IDÊNTICA à de getAccountsPayableExpenses.
     * @return Collection
     */
    public function getProjectedExpensesByCostCenter(): Collection
    {
        $costs = GigCost::where('is_confirmed', false)
            // Garante que a Gig associada não foi deletada (soft delete)
            ->whereHas('gig', function ($gigQuery) { // Adiciona a verificação da Gig aqui
                $gigQuery->whereNull('deleted_at'); // <-- SÓ GIGS ATIVAS
            })
            ->where(function ($query) { // Lógica de data para as despesas
                // Condição 1
                $query->where(function($q1) {
                    $q1->whereNotNull('expense_date')
                       ->where('expense_date', '<=', $this->endDate);
                })
                // Condição 2
                      ->orWhere(function($q2){
                          $q2->whereNull('expense_date')
                             ->whereHas('gig', function ($gigSubQuery) { // whereHas já está aqui
                                 $gigSubQuery->where('gig_date', '<=', $this->endDate);
                             });
                      });
            })
            ->with(['costCenter', 'gig.artist']) // Eager load
            ->get();

        Log::info("[FinancialProjectionService] Quantidade de custos para getProjectedExpensesByCostCenter: " . $costs->count());

        return $costs->groupBy('cost_center_id') // 1. Agrupa pelo ID
        ->map(function ($group, $costCenterId) {
            $firstCost = $group->first();

            // 2. Determina o nome traduzido do grupo
            $groupName = 'Sem Centro de Custo';
            if ($firstCost && $firstCost->costCenter) {
                $groupName = __('cost_centers.' . $firstCost->costCenter->name);
            }
            
            $totalBrl = $group->sum('value_brl');
            $expenses = $group->map(function ($cost) {
                $orderByDate = null;
                $isGigDateFallback = false;

                if ($cost->expense_date) {
                    $orderByDate = Carbon::parse($cost->expense_date);
                } elseif ($cost->gig && $cost->gig->gig_date) {
                    $orderByDate = Carbon::parse($cost->gig->gig_date);
                    $isGigDateFallback = true;
                }

                return [
                    'gig_id' => $cost->gig_id,
                    'gig_contract_number' => $cost->gig->contract_number ?? 'Gig #'.$cost->gig_id,
                    'gig_artist_name' => $cost->gig->artist->name ?? 'N/A',
                    'description' => $cost->description,
                    'expense_date_formatted' => $orderByDate ? $orderByDate->format('d/m/Y') . ($isGigDateFallback ? ' (Gig)' : '') : 'N/A',
                    'value_brl' => (float) $cost->value_brl,
                    'currency' => strtoupper($cost->currency ?? 'BRL'),
                    '_order_by_date_object' => $orderByDate,
                ];
            })->sortBy(function ($expense) {
                return $expense['_order_by_date_object'] ? $expense['_order_by_date_object']->timestamp : PHP_INT_MAX;
            });

            return [
                'cost_center_name' => $groupName,
                'total_brl' => $totalBrl,
                'expenses' => $expenses->values(),
            ];
        })->sortBy('cost_center_name')->values();
    }

    // Fluxo de Caixa Projetado
    public function getProjectedCashFlow(): float
    {
        $receivable = $this->getAccountsReceivable();
        $payableArtists = $this->getAccountsPayableArtists();
        $payableBookers = $this->getAccountsPayableBookers();
        $payableExpenses = $this->getAccountsPayableExpenses(); // Este usará a lógica corrigida
        
        $totalPayable = $payableArtists + $payableBookers + $payableExpenses;
        $cashFlow = $receivable - $totalPayable;
        
        Log::info("[FinancialProjectionService] Fluxo de Caixa Projetado: Recebível {$receivable} - Total a Pagar {$totalPayable} (Artistas {$payableArtists} + Bookers {$payableBookers} + Despesas {$payableExpenses}) = {$cashFlow}");
        
        return (float) $cashFlow;
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
            ->when($type === 'bookers', function ($query) {
                $query->whereNotNull('booker_id');
            })
            ->where(function ($query) { 
                $query->where('gig_date', '<', $this->startDate) 
                      ->orWhereBetween('gig_date', [$this->startDate, $this->endDate]); 
            })
            ->with(['artist', 'booker'])
            ->orderBy('gig_date')
            ->get();
    }
}