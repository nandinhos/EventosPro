<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Payment;
use App\Models\Gig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DelinquencyReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Buscamos os IDs das Gigs que têm parcelas pendentes e que passam pelos filtros.
        $gigIdsQuery = Gig::query()
            ->whereNull('deleted_at') // Apenas gigs ativas
            ->whereHas('payments', function ($query) {
                $query->whereNull('confirmed_at'); // Que tenham ao menos uma parcela pendente
            });

        // Aplicar filtros de Gig (data do evento, artista, booker)
        if ($request->filled('event_start_date')) {
            $gigIdsQuery->where('gig_date', '>=', $request->input('event_start_date'));
        }
        if ($request->filled('event_end_date')) {
            $gigIdsQuery->where('gig_date', '<=', $request->input('event_end_date'));
        }
        if ($request->filled('artist_id')) {
            $gigIdsQuery->where('artist_id', $request->input('artist_id'));
        }
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') {
                $gigIdsQuery->whereNull('booker_id');
            } else {
                $gigIdsQuery->where('booker_id', $request->input('booker_id'));
            }
        }
        if ($request->filled('currency') && $request->input('currency') !== 'all') {
            // Este filtro agora é na moeda do CONTRATO da Gig
            $gigIdsQuery->where('currency', $request->input('currency'));
        }

        // Se houver filtros de data de vencimento, aplicamos também
        if ($request->filled('due_start_date') || $request->filled('due_end_date')) {
            $gigIdsQuery->whereHas('payments', function ($q) use ($request) {
                $q->whereNull('confirmed_at');
                if ($request->filled('due_start_date')) {
                    $q->where('due_date', '>=', $request->input('due_start_date'));
                }
                if ($request->filled('due_end_date')) {
                    $q->where('due_date', '<=', $request->input('due_end_date'));
                }
            });
        }
        
        $relevantGigIds = $gigIdsQuery->pluck('id');

        // 2. Buscamos as Gigs completas, com seus relacionamentos, e ordenamos por Booker
        $gigsWithPendingPayments = Gig::whereIn('id', $relevantGigIds)
            ->with([
                'artist', 
                'booker', 
                'payments' => function ($query) {
                    $query->orderBy('due_date', 'asc'); // Ordena as parcelas de cada gig
                }
            ])
            ->get()
            ->sortBy('booker.name'); // Ordena as Gigs pelo nome do booker

        // 3. Agrupamos as Gigs pelo nome do booker (ou 'Agência Direta')
        $gigsGroupedByBooker = $gigsWithPendingPayments->groupBy(function ($gig) {
            return $gig->booker->name ?? 'Agência Direta';
        });

        // Dados para os filtros
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $currencies = Gig::select('currency')->distinct()->orderBy('currency')->pluck('currency');

        // Cálculo do Resumo Financeiro Geral (agora baseado nas Gigs filtradas)
        $totalContractValueConsolidatedBRL = 0;
        $totalReceivedValueBRL = 0;
        $totalPendingByOtherCurrency = [];

        foreach ($gigsWithPendingPayments as $gig) {
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
            'gigsGroupedByBooker', // Passa a coleção agrupada por Booker
            'artists',
            'bookers',
            'currencies',
            'totalContractValueConsolidatedBRL',
            'totalReceivedValueBRL',
            'totalPendingByOtherCurrency'
        ));
    }

    public function exportPdf(Request $request)
    {
        // 1. Replicar a mesma lógica de busca e filtragem do método index()
        $gigIdsQuery = Gig::query()->whereNull('deleted_at')->whereHas('payments', fn($q) => $q->whereNull('confirmed_at'));
        
        if ($request->filled('event_start_date')) $gigIdsQuery->where('gig_date', '>=', $request->input('event_start_date'));
        if ($request->filled('event_end_date')) $gigIdsQuery->where('gig_date', '<=', $request->input('event_end_date'));
        // ... (adicione aqui TODAS as outras lógicas de filtro do seu método index) ...
        if ($request->filled('artist_id')) $gigIdsQuery->where('artist_id', $request->input('artist_id'));
        if ($request->filled('booker_id')) {
             if ($request->input('booker_id') === 'sem_booker') $gigIdsQuery->whereNull('booker_id');
             else $gigIdsQuery->where('booker_id', $request->input('booker_id'));
        }

        $relevantGigIds = $gigIdsQuery->pluck('id');
        
        $gigsWithPendingPayments = Gig::whereIn('id', $relevantGigIds)
            ->with(['artist', 'booker', 'payments' => fn($q) => $q->orderBy('due_date', 'asc')])
            ->get()
            ->sortBy('booker.name');
        
        $gigsGroupedByBooker = $gigsWithPendingPayments->groupBy(fn($gig) => $gig->booker->name ?? 'Agência Direta');
        
        $filters = $request->only(['event_start_date', 'event_end_date', 'due_start_date', 'due_end_date', 'artist_id', 'booker_id']);

        // 2. Carregar a view do PDF com os dados
        $pdf = Pdf::loadView('reports.exports.delinquency_pdf', [
            'gigsGroupedByBooker' => $gigsGroupedByBooker,
            'filters' => $filters
        ]);

        // 3. Definir o nome do arquivo e fazer o download
        $fileName = 'relatorio_pendencias_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($fileName);
    }

}