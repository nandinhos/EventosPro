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
        // Modifica a query para buscar TODAS as parcelas NÃO CONFIRMADAS
        $query = Payment::query()
             
            ->with(['gig' => function ($gigQuery) {
                $gigQuery->with(['artist', 'booker', 'payments'])
                         ->whereNull('deleted_at');
            }])
            ->whereHas('gig', function ($gigQuery) { // Garante que a gig exista e não foi deletada
                $gigQuery->whereNull('deleted_at');
            });

        // Aplicar filtros (a lógica de filtros pode ser mantida)
        // Se um filtro de data de vencimento for aplicado, ele restringirá as parcelas.
        // Se não, mostrará todas as pendentes.
        if ($request->filled('event_start_date')) {
            $query->whereHas('gig', fn ($q) => $q->where('gig_date', '>=', $request->input('event_start_date')));
        }
        if ($request->filled('event_end_date')) {
            $query->whereHas('gig', fn ($q) => $q->where('gig_date', '<=', $request->input('event_end_date')));
        }
        // Filtros de VENCIMENTO da parcela continuam úteis para focar em períodos específicos de vencimento
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

        // Ordena os pagamentos para que, ao agrupar, as Gigs apareçam e as parcelas dentro delas
        // fiquem ordenadas por vencimento.
        $allPendingPayments = $query->orderBy('gig_id')->orderBy('due_date', 'asc')->get();

        // Agrupar pagamentos por Gig
        $pendingPaymentsGroupedByGig = $allPendingPayments->groupBy('gig_id')
            ->map(function ($paymentsForGig, $gigId) {
                $firstPayment = $paymentsForGig->first();
                if (!$firstPayment || !$firstPayment->gig) { // Checagem de segurança
                    return null; 
                }
                return [
                    'gig' => $firstPayment->gig,
                    'payments' => $paymentsForGig
                ];
            })->filter(); // Remove quaisquer entradas nulas se uma gig não pôde ser carregada

        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $currencies = Payment::select('currency')->distinct()->orderBy('currency')->pluck('currency');

        // Cálculo do Resumo Financeiro Geral para Gigs com Pendências
        $uniqueGigsWithPending = new \Illuminate\Database\Eloquent\Collection();
        foreach ($pendingPaymentsGroupedByGig as $group) {
            if ($group['gig'] && !$uniqueGigsWithPending->contains('id', $group['gig']->id)) {
                $uniqueGigsWithPending->push($group['gig']);
            }
        }
        
        $totalContractValueConsolidatedBRL = 0;
        $totalReceivedValueBRL = 0;
        $totalPendingByOtherCurrency = []; 

        foreach ($uniqueGigsWithPending as $gig) {
            $cacheBrlDetails = $gig->cacheValueBrlDetails;

            if (strtoupper($gig->currency) === 'BRL') {
                $totalContractValueConsolidatedBRL += $gig->cache_value;
            } elseif ($cacheBrlDetails['type'] === 'confirmed' && $cacheBrlDetails['value'] !== null) {
                $totalContractValueConsolidatedBRL += $cacheBrlDetails['value'];
            }

            $gigTotalReceivedBRL = $gig->payments
                                    ->whereNotNull('confirmed_at')
                                    ->sum(function($p) {
                                        if (strtoupper($p->currency) === 'BRL') return $p->received_value_actual;
                                        $rate = $p->exchange_rate_received_actual ?: ($p->exchange_rate ?: 1);
                                        return $p->received_value_actual * $rate;
                                    });
            $totalReceivedValueBRL += $gigTotalReceivedBRL;

            if (strtoupper($gig->currency) !== 'BRL') {
                $pendingOriginalForGig = $gig->cache_value - $gig->payments->whereNotNull('confirmed_at')->where('currency', $gig->currency)->sum('received_value_actual');
                if ($pendingOriginalForGig > 0.009) { // Considera apenas se houver pendência real
                    $totalPendingByOtherCurrency[$gig->currency] = ($totalPendingByOtherCurrency[$gig->currency] ?? 0) + $pendingOriginalForGig;
                }
            }
        }
        $totalPendingValueConsolidatedBRL = $totalContractValueConsolidatedBRL - $totalReceivedValueBRL;

        return view('reports.delinquency', compact(
            'pendingPaymentsGroupedByGig', // Nome da variável alterado
            'artists',
            'bookers',
            'currencies',
            'totalContractValueConsolidatedBRL',
            'totalReceivedValueBRL',
            'totalPendingValueConsolidatedBRL',
            'totalPendingByOtherCurrency'
        ));
    }
}