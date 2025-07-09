<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookerRequest;
use App\Http\Requests\UpdateBookerRequest;
use App\Models\Booker;
use App\Services\BookerFinancialsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookerController extends Controller
{
    protected BookerFinancialsService $financialService;

    public function __construct(BookerFinancialsService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index(): View
    {
        
        $bookers = Booker::withCount('gigs')
                         ->orderBy('name')
                         ->paginate(20); 

        return view('bookers.index', compact('bookers'));
    }

    public function create(): View
    {
        return view('bookers.create');
    }

    public function store(StoreBookerRequest $request): RedirectResponse
    {
        Booker::create($request->validated());
        return redirect()->route('bookers.index')->with('success', 'Booker criado com sucesso.');
    }

   public function show(Booker $booker, Request $request): View
    {
        $filters = $request->only(['start_date', 'end_date']);
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        $salesKpis = $this->financialService->getSalesKpis($booker, $startDate, $endDate);
        
        $commissionKpis = $this->financialService->getCommissionKpis($booker);
        $chart = $this->financialService->getCommissionChartData($booker);
        $topArtists = $this->financialService->getTopArtists($booker); // Top artistas "lifetime"
        $recentGigs = $this->financialService->getRecentGigs($booker);

        $analyticalTableData = collect();
        if ($startDate && $endDate) {
            $analyticalTableData = $this->financialService->getGigsForPeriod($booker, $startDate, $endDate);
        }
        
        return view('bookers.show', [
            'booker' => $booker,
            'filters' => $filters,
            'salesKpis' => $salesKpis,
            'commissionKpis' => $commissionKpis,
            'chart' => $chart,
            'topArtists' => $topArtists,
            'recentGigs' => $recentGigs,
            'analyticalTableData' => $analyticalTableData
        ]);
    }

    public function edit(Booker $booker): View
    {
        return view('bookers.edit', compact('booker'));
    }

    public function update(UpdateBookerRequest $request, Booker $booker): RedirectResponse
    {
        $booker->update($request->validated());
        return redirect()->route('bookers.index')->with('success', 'Booker atualizado com sucesso.');
    }

    public function destroy(Booker $booker): RedirectResponse
    {
        // Lógica para soft delete
        $booker->delete();
        return redirect()->route('bookers.index')->with('success', 'Booker removido com sucesso.');
    }
}