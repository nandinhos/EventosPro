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
                $gigQuery->with(['artist', 'booker', 'payments'])
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

        // --- AJUSTE NO CÁLCULO DO RESUMO FINANCEIRO GERAL ---
        $uniqueGigsWithDelinquency = new \Illuminate\Database\Eloquent\Collection();
        foreach ($delinquentPaymentsGroupedByGig as $group) {
            if ($group['gig'] && !$uniqueGigsWithDelinquency->contains('id', $group['gig']->id)) {
                $uniqueGigsWithDelinquency->push($group['gig']);
            }
        }
        
        $totalContractValueConsolidatedBRL = 0; // Apenas BRL ou convertido confirmado
        $totalReceivedValueBRL = 0;             // Sempre BRL
        // Para pendências em outras moedas no resumo geral (Opcional)
        $totalPendingByOtherCurrency = []; 

        foreach ($uniqueGigsWithDelinquency as $gig) {
            $cacheBrlDetails = $gig->cacheValueBrlDetails;

            if (strtoupper($gig->currency) === 'BRL') {
                $totalContractValueConsolidatedBRL += $gig->cache_value; // Já é BRL
            } elseif ($cacheBrlDetails['type'] === 'confirmed' && $cacheBrlDetails['value'] !== null) {
                $totalContractValueConsolidatedBRL += $cacheBrlDetails['value'];
            }
            // Se for moeda estrangeira e não confirmada, não adicionamos ao total BRL consolidado por enquanto.

            // Total Recebido BRL (como antes, mas agora iterando sobre as gigs filtradas)
            $gigTotalReceivedBRL = $gig->payments
                                    ->whereNotNull('confirmed_at')
                                    ->sum(function($p) {
                                        if (strtoupper($p->currency) === 'BRL') return $p->received_value_actual;
                                        return $p->received_value_actual * ($p->exchange_rate_received_actual ?: ($p->exchange_rate ?: 1));
                                    });
            $totalReceivedValueBRL += $gigTotalReceivedBRL;

            // Para pendências em outras moedas (Opcional para o resumo geral)
            if (strtoupper($gig->currency) !== 'BRL') {
                $pendingOriginalForGig = $gig->cache_value - $gig->payments->whereNotNull('confirmed_at')->where('currency', $gig->currency)->sum('received_value_actual');
                if ($pendingOriginalForGig > 0) {
                    if (!isset($totalPendingByOtherCurrency[$gig->currency])) {
                        $totalPendingByOtherCurrency[$gig->currency] = 0;
                    }
                    $totalPendingByOtherCurrency[$gig->currency] += $pendingOriginalForGig;
                }
            }
        }
        $totalPendingValueConsolidatedBRL = $totalContractValueConsolidatedBRL - $totalReceivedValueBRL;

        return view('reports.delinquency', compact(
            'delinquentPaymentsGroupedByGig',
            'artists',
            'bookers',
            'currencies',
            'totalContractValueConsolidatedBRL', // Nome da variável para o resumo
            'totalReceivedValueBRL',
            'totalPendingValueConsolidatedBRL',  // Nome da variável para o resumo
            'totalPendingByOtherCurrency'        // Para exibir pendências em outras moedas no resumo
        ));
    }
}