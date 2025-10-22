<?php

namespace App\Services;

use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Query Builder especializado para Projeções Financeiras.
 * Centraliza queries complexas com eager loading otimizado.
 */
class ProjectionQueryBuilder
{
    /**
     * Retorna query builder para pagamentos pendentes no período.
     * Inclui vencidos + a vencer no período especificado.
     *
     * @param  Carbon  $startDate  Data inicial do período
     * @param  Carbon  $endDate  Data final do período
     * @param  bool  $execute  Se true, executa e retorna Collection; se false, retorna Builder
     * @return Builder|Collection
     */
    public function pendingPaymentsQuery(Carbon $startDate, Carbon $endDate, bool $execute = true)
    {
        $query = Payment::query()
            ->select([
                'id',
                'gig_id',
                'due_date',
                'due_value',
                'currency',
                'confirmed_at',
            ])
            ->whereNull('confirmed_at')
            ->whereHas('gig') // Garante que a gig não foi deletada
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('due_date', '<', $startDate) // Vencidas
                    ->orWhereBetween('due_date', [$startDate, $endDate]); // A vencer no período
            })
            ->with([
                'gig' => function ($query) {
                    $query->select(['id', 'location_event_details', 'artist_id', 'contract_number'])
                        ->with('artist:id,name');
                },
            ])
            ->orderBy('due_date');

        return $execute ? $query->get() : $query;
    }

    /**
     * Retorna query builder para gigs com pagamentos pendentes (artistas ou bookers).
     *
     * @param  Carbon  $startDate  Data inicial do período
     * @param  Carbon  $endDate  Data final do período
     * @param  string  $paymentType  'artists' ou 'bookers'
     * @param  bool  $execute  Se true, executa e retorna Collection; se false, retorna Builder
     * @return Builder|Collection
     */
    public function pendingGigsQuery(Carbon $startDate, Carbon $endDate, string $paymentType, bool $execute = true)
    {
        $statusColumn = ($paymentType === 'artists') ? 'artist_payment_status' : 'booker_payment_status';

        $query = Gig::query()
            ->select([
                'id',
                'location_event_details',
                'gig_date',
                'artist_id',
                'booker_id',
                'cache_value',
                'currency',
                'agency_commission_type',
                'agency_commission_rate',
                'agency_commission_value',
                'booker_commission_type',
                'booker_commission_rate',
                'booker_commission_value',
                $statusColumn,
            ])
            ->where($statusColumn, 'pendente')
            ->when($paymentType === 'bookers', function ($query) {
                $query->whereNotNull('booker_id');
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('gig_date', '<', $startDate) // Passadas
                    ->orWhereBetween('gig_date', [$startDate, $endDate]); // Futuras no período
            })
            ->with([
                'artist:id,name',
                'booker:id,name',
                'gigCosts' => function ($query) {
                    $query->select(['id', 'gig_id', 'value', 'currency', 'expense_date', 'is_confirmed', 'is_invoice'])
                        ->where('is_confirmed', true);
                },
            ])
            ->orderBy('gig_date');

        return $execute ? $query->get() : $query;
    }

    /**
     * Retorna query builder para despesas não confirmadas no período.
     *
     * @param  Carbon  $startDate  Data inicial do período
     * @param  Carbon  $endDate  Data final do período
     * @param  bool  $execute  Se true, executa e retorna Collection; se false, retorna Builder
     * @return Builder|Collection
     */
    public function pendingExpensesQuery(Carbon $endDate, bool $execute = true)
    {
        $query = GigCost::query()
            ->select([
                'id',
                'gig_id',
                'cost_center_id',
                'description',
                'value',
                'currency',
                'expense_date',
                'is_confirmed',
            ])
            ->where('is_confirmed', false)
            ->whereHas('gig', function ($gigQuery) {
                $gigQuery->whereNull('deleted_at');
            })
            ->where(function ($query) use ($endDate) {
                // Despesa tem data E está no período
                $query->where(function ($q1) use ($endDate) {
                    $q1->whereNotNull('expense_date')
                        ->where('expense_date', '<=', $endDate);
                })
                    // OU despesa NÃO tem data, mas Gig está no período
                    ->orWhere(function ($q2) use ($endDate) {
                        $q2->whereNull('expense_date')
                            ->whereHas('gig', function ($gigSubQuery) use ($endDate) {
                                $gigSubQuery->where('gig_date', '<=', $endDate);
                            });
                    });
            })
            ->with([
                'gig:id,contract_number,artist_id,gig_date',
                'gig.artist:id,name',
                'costCenter:id,name',
            ]);

        return $execute ? $query->get() : $query;
    }

    /**
     * Retorna query builder para pagamentos vencidos (análise de inadimplência).
     *
     * @param  bool  $execute  Se true, executa e retorna Collection; se false, retorna Builder
     * @return Builder|Collection
     */
    public function overduePaymentsQuery(bool $execute = true)
    {
        $today = Carbon::today();

        $query = Payment::query()
            ->select([
                'id',
                'gig_id',
                'due_date',
                'due_value',
                'currency',
                'confirmed_at',
            ])
            ->whereNull('confirmed_at')
            ->whereHas('gig')
            ->where('due_date', '<', $today)
            ->with(['gig.artist:id,name'])
            ->orderBy('due_date');

        return $execute ? $query->get() : $query;
    }

    /**
     * Retorna query builder para eventos futuros no período (análise de eventos).
     *
     * @param  Carbon  $startDate  Data inicial do período
     * @param  Carbon  $endDate  Data final do período
     * @param  bool  $execute  Se true, executa e retorna Collection; se false, retorna Builder
     * @return Builder|Collection
     */
    public function futureEventsQuery(Carbon $startDate, Carbon $endDate, bool $execute = true)
    {
        $query = Gig::query()
            ->select([
                'id',
                'location_event_details',
                'gig_date',
                'artist_id',
                'booker_id',
                'cache_value',
                'currency',
                'agency_commission_type',
                'agency_commission_rate',
                'agency_commission_value',
            ])
            ->whereBetween('gig_date', [$startDate, $endDate])
            ->with([
                'artist:id,name',
                'booker:id,name',
                'gigCosts' => function ($query) {
                    $query->select(['id', 'gig_id', 'value', 'currency', 'expense_date', 'is_confirmed'])
                        ->where('is_confirmed', true);
                },
            ])
            ->orderBy('gig_date');

        return $execute ? $query->get() : $query;
    }

    /**
     * Executa todas as queries necessárias para métricas globais de forma otimizada.
     * Retorna array com dados consolidados.
     */
    public function fetchGlobalProjectionData(): array
    {
        // Período global: do passado ao futuro distante
        $startDate = Carbon::create(2000, 1, 1);
        $endDate = Carbon::create(2100, 12, 31);

        // Executa queries em paralelo (conceitual - Laravel executa sequencialmente)
        return [
            'pending_payments' => $this->pendingPaymentsQuery($startDate, $endDate, true),
            'pending_gigs_artists' => $this->pendingGigsQuery($startDate, $endDate, 'artists', true),
            'pending_gigs_bookers' => $this->pendingGigsQuery($startDate, $endDate, 'bookers', true),
            'pending_expenses' => $this->pendingExpensesQuery($endDate, true),
            'overdue_payments' => $this->overduePaymentsQuery(true),
        ];
    }

    /**
     * Executa todas as queries necessárias para métricas de período específico.
     *
     * @param  Carbon  $startDate  Data inicial do período
     * @param  Carbon  $endDate  Data final do período
     */
    public function fetchPeriodProjectionData(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'pending_payments' => $this->pendingPaymentsQuery($startDate, $endDate, true),
            'pending_gigs_artists' => $this->pendingGigsQuery($startDate, $endDate, 'artists', true),
            'pending_gigs_bookers' => $this->pendingGigsQuery($startDate, $endDate, 'bookers', true),
            'pending_expenses' => $this->pendingExpensesQuery($endDate, true),
            'future_events' => $this->futureEventsQuery($startDate, $endDate, true),
        ];
    }
}
