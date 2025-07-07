<?php

namespace App\Http\Controllers;

use App\Models\Booker;
use App\Models\Artist;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGigRequest;
use App\Http\Requests\UpdateGigRequest;
use App\Models\Gig;
use App\Http\Requests\StoreBookerRequest;
use App\Http\Requests\UpdateBookerRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Para transação, se necessário
use App\Services\BookerFinancialsService;


class BookerController extends Controller
{
    protected BookerFinancialsService $financialService;

    public function __construct(BookerFinancialsService $financialService)
    {
        $this->financialService = $financialService;
    }
    
    /** Display a listing of the resource. */
    public function index(Request $request): View
    {
        $query = Booker::withCount('gigs')->latest(); // Ordena por mais recente, conta gigs

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $bookers = $query->paginate(20)->withQueryString();
        return view('bookers.index', compact('bookers'));
    }

    /** Show the form for creating a new resource. */
    public function create(): View
    {
        return view('bookers.create'); // Passa um booker vazio para o form parcial
    }

    /** Store a newly created resource in storage. */
    public function store(StoreBookerRequest $request): RedirectResponse
    {
        try {
            Booker::create($request->validated());
            return redirect()->route('bookers.index')->with('success', 'Booker criado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar Booker: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao criar booker.');
        }
    }

    /**
     * Display the specified resource as a performance dashboard.
     */
    public function show(Booker $booker): View
    {
        $periodStart = now()->subYear();
        $periodEnd = now();

        // ***** CORREÇÃO: Passa $booker como primeiro argumento para cada método *****
        $salesKpis = $this->financialService->getSalesKpis($booker, $periodStart, $periodEnd);
        $commissionKpis = $this->financialService->getCommissionMetrics($booker);
        $chart = $this->financialService->getCommissionChartData($booker);
        $topArtists = $this->financialService->getTopArtists($booker);
        $recentGigs = $this->financialService->getRecentGigs($booker);

        return view('bookers.show', [
            'booker' => $booker,
            'totalSoldValue' => $salesKpis['total_sold_value'],
            'totalGigsSold' => $salesKpis['total_gigs_sold'],
            'commissionReceived' => $commissionKpis['commission_received'],
            'commissionToReceive' => $commissionKpis['commission_to_receive'],
            'chartLabels' => $chart['labels'],
            'chartData' => $chart['data'],
            'topArtists' => $topArtists,
            'recentGigs' => $recentGigs,
        ]);
    }

    /** Show the form for editing the specified resource. */
    public function edit(Booker $booker): View
    {
        return view('bookers.edit', compact('booker'));
    }

    /** Update the specified resource in storage. */
    public function update(UpdateBookerRequest $request, Booker $booker): RedirectResponse
    {
         try {
            $booker->update($request->validated());
            return redirect()->route('bookers.index')->with('success', 'Booker atualizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar Booker: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao atualizar booker.');
        }
    }

    /** Remove the specified resource from storage. */
    public function destroy(Booker $booker): RedirectResponse
    {
        // Adicionar verificação se o booker tem gigs futuras antes de excluir?
        try {
            $booker->delete(); // Soft delete
            return redirect()->route('bookers.index')->with('success', 'Booker excluído com sucesso!');
        } catch (\Exception $e) {
             Log::error('Erro ao excluir Booker: ' . $e->getMessage());
            return back()->with('error', 'Erro ao excluir booker.');
        }
    }
}