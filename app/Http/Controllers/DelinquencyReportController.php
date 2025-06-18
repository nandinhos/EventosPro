<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Payment;
use App\Models\Gig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DelinquencyReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query()
            ->whereNull('confirmed_at')
            ->where('due_date', '<', Carbon::today())
            ->with(['gig' => function ($gigQuery) {
                $gigQuery->with(['artist', 'booker'])
                         ->whereNull('deleted_at');
            }])
            ->whereHas('gig', function ($gigQuery) {
                $gigQuery->whereNull('deleted_at');
            });

        // Aplicar filtros (mantém a lógica de filtros anterior)
        if ($request->filled('event_start_date')) {
            $query->whereHas('gig', fn ($q) => $q->where('gig_date', '>=', $request->input('event_start_date')));
        }
        if ($request->filled('event_end_date')) {
            $query->whereHas('gig', fn ($q) => $q->where('gig_date', '<=', $request->input('event_end_date')));
        }
        if ($request->filled('due_start_date')) {
            $query->where('due_date', '>=', $request->input('due_start_date'));
        }
        if ($request->filled('due_end_date')) {
            $query->where('due_date', '<=', $request->input('due_end_date'));
        }
        if ($request->filled('artist_id')) {
            $query->whereHas('gig.artist', fn ($q) => $q->where('artists.id', $request->input('artist_id')));
        }
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') {
                $query->whereHas('gig', fn ($q) => $q->whereNull('booker_id'));
            } else {
                $query->whereHas('gig.booker', fn ($q) => $q->where('bookers.id', $request->input('booker_id')));
            }
        }
        if ($request->filled('currency') && $request->input('currency') !== 'all') {
            $query->where('payments.currency', $request->input('currency'));
        }

        // Ordenação: Primeiro por data da gig (para agrupar visualmente), depois por data de vencimento da parcela
        // Para ordenar por um campo da relação 'gig', precisamos de um join ou uma abordagem diferente
        // Por agora, vamos ordenar os pagamentos e agrupar depois. A ordenação dos grupos será pelo ID da Gig.
        $allDelinquentPayments = $query->orderBy('gig_id')->orderBy('due_date', 'asc')->get();

        // ***** NOVA LÓGICA: Agrupar pagamentos por Gig *****
        $delinquentPaymentsGroupedByGig = $allDelinquentPayments->groupBy('gig_id')
            ->map(function ($paymentsForGig, $gigId) {
                // A primeira parcela do grupo terá a informação completa da Gig
                $firstPayment = $paymentsForGig->first();
                return [
                    'gig' => $firstPayment->gig, // Objeto Gig completo
                    'payments' => $paymentsForGig // Coleção de parcelas para esta Gig
                ];
            });
        // Não precisamos mais de paginação aqui se vamos mostrar todos os grupos e suas parcelas.
        // Se a lista de GIGS com inadimplência for muito grande, podemos paginar $delinquentPaymentsGroupedByGig.
        // Para paginação de $delinquentPaymentsGroupedByGig, precisaria de uma abordagem mais manual com LengthAwarePaginator.

        // Dados para os filtros na view
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $currencies = Payment::select('currency')->distinct()->orderBy('currency')->pluck('currency');

        // Cálculo do Resumo Financeiro Geral para Gigs Inadimplentes (apenas as que aparecerão na lista agrupada)
        $totalContractValueBRL = 0;
        $totalReceivedValueBRL = 0;

        foreach ($delinquentPaymentsGroupedByGig as $group) {
            if ($group['gig']) {
                $totalContractValueBRL += $group['gig']->cache_value_brl;
                $totalReceivedValueBRL += $group['gig']->payments->whereNotNull('confirmed_at')->sum(function($p){
                    return $p->currency === 'BRL' ? $p->received_value_actual : ($p->received_value_actual * ($p->exchange_rate ?? 1));
                });
            }
        }
        $totalPendingValueBRL = $totalContractValueBRL - $totalReceivedValueBRL;

        return view('reports.delinquency', compact(
            'delinquentPaymentsGroupedByGig', // Passa a coleção agrupada
            'artists',
            'bookers',
            'currencies',
            // 'sortBy', 'sortDirection' // A ordenação agora é mais complexa dentro dos grupos
            'totalContractValueBRL',
            'totalReceivedValueBRL',
            'totalPendingValueBRL'
        ));
    }
}