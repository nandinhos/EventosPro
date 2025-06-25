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
        $request->validate(['include_paid' => 'nullable|boolean']);
        $includePaidGigs = $request->boolean('include_paid');

        $gigQuery = Gig::query()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($includePaidGigs) {
                $query->whereHas('payments', fn($q) => $q->whereNull('confirmed_at'))
                      ->when($includePaidGigs, function ($q) {
                          $q->orWhere(fn($sub) => $sub->doesntHave('payments', 'and', fn($p) => $p->whereNull('confirmed_at')));
                      });
            });

        // Aplicar filtros
        if ($request->filled('event_start_date')) $gigQuery->where('gig_date', '>=', $request->input('event_start_date'));
        if ($request->filled('event_end_date')) $gigQuery->where('gig_date', '<=', $request->input('event_end_date'));
        if ($request->filled('artist_id')) $gigQuery->where('artist_id', $request->input('artist_id'));
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') $gigQuery->whereNull('booker_id');
            else $gigQuery->where('booker_id', $request->input('booker_id'));
        }
        if ($request->filled('currency') && $request->input('currency') !== 'all') $gigQuery->where('currency', $request->input('currency'));
        if ($request->filled('due_start_date') || $request->filled('due_end_date')) {
            $gigQuery->whereHas('payments', function ($q) use ($request) {
                if ($request->filled('due_start_date')) $q->where('due_date', '>=', $request->input('due_start_date'));
                if ($request->filled('due_end_date')) $q->where('due_date', '<=', $request->input('due_end_date'));
            });
        }
        
        $relevantGigs = $gigQuery
            ->with(['artist', 'booker', 'payments' => fn($q) => $q->orderBy('due_date', 'asc')])
            ->get()
            ->sortBy('booker.name');

        $gigsGroupedByBooker = $relevantGigs->groupBy(fn($gig) => $gig->booker->name ?? 'Agência Direta');

        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $currencies = Gig::select('currency')->distinct()->orderBy('currency')->pluck('currency');

        // ***** INÍCIO DA CORREÇÃO NO CÁLCULO *****
        $totalContractValueConsolidatedBRL = 0;
        $totalReceivedValueBRL = 0;
        $totalPendingByOtherCurrency = [];

        // ***** Variável correta usada aqui: $relevantGigs *****
        foreach ($relevantGigs as $gig) { 
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
                if ($pendingOriginalForGig > 0.009) {
                    $totalPendingByOtherCurrency[$gig->currency] = ($totalPendingByOtherCurrency[$gig->currency] ?? 0) + $pendingOriginalForGig;
                }
            }
        }
        $totalPendingValueConsolidatedBRL = $totalContractValueConsolidatedBRL - $totalReceivedValueBRL;
        // ***** FIM DA CORREÇÃO NO CÁLCULO *****

        return view('reports.delinquency', compact(
            'gigsGroupedByBooker',
            'artists',
            'bookers',
            'currencies',
            'totalContractValueConsolidatedBRL',
            'totalReceivedValueBRL',
            'totalPendingValueConsolidatedBRL',
            'totalPendingByOtherCurrency'
        ));
    }

    public function exportPdf(Request $request)
    {
        $request->validate(['include_paid' => 'nullable|boolean']);
        $includePaidGigs = $request->boolean('include_paid');

        $gigQuery = Gig::query()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($includePaidGigs) {
                $query->whereHas('payments', fn($q) => $q->whereNull('confirmed_at'))
                      ->when($includePaidGigs, function ($q) {
                          $q->orWhere(fn($sub) => $sub->doesntHave('payments', 'and', fn($p) => $p->whereNull('confirmed_at')));
                      });
            });

        if ($request->filled('event_start_date')) $gigQuery->where('gig_date', '>=', $request->input('event_start_date'));
        if ($request->filled('event_end_date')) $gigQuery->where('gig_date', '<=', $request->input('event_end_date'));
        if ($request->filled('artist_id')) $gigQuery->where('artist_id', $request->input('artist_id'));
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') $gigQuery->whereNull('booker_id');
            else $gigQuery->where('booker_id', $request->input('booker_id'));
        }

        $relevantGigs = $gigQuery
            ->with(['artist', 'booker', 'payments' => fn($q) => $q->orderBy('due_date', 'asc')])
            ->get()
            ->sortBy('booker.name');

        $gigsGroupedByBooker = $relevantGigs->groupBy(fn($gig) => $gig->booker->name ?? 'Agência Direta');

        $filters = $request->only(['event_start_date', 'event_end_date']);

        $pdf = Pdf::loadView('reports.exports.delinquency_pdf', [
            'gigsGroupedByBooker' => $gigsGroupedByBooker,
            'filters' => $filters
        ]);

        $fileName = 'relatorio_pendencias_' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($fileName);
    }

}