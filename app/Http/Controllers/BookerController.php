<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookerRequest;
use App\Http\Requests\UpdateBookerRequest;
use App\Models\Booker;
use App\Services\BookerFinancialsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'analyticalTableData' => $analyticalTableData,
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

    /**
     * Mostra o portal de desempenho para o booker logado.
     */
    public function portal(Request $request): View|RedirectResponse
    {
        // 1. Pega o usuário logado
        $user = Auth::user();

        // 2. Verifica se o usuário tem um booker associado
        if (! $user->booker_id) {
            // Se não for um booker, redireciona para o dashboard principal com um erro.
            return redirect()->route('dashboard')->with('error', 'Acesso não permitido.');
        }

        // 3. Pega a entidade Booker associada
        $booker = $user->booker;

        // 4. Reutiliza a mesma lógica do método show() para buscar os dados
        $filters = $request->only(['start_date', 'end_date']);
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        $salesKpis = $this->financialService->getSalesKpis($booker, $startDate, $endDate);
        $commissionKpis = $this->financialService->getCommissionKpis($booker);
        $chart = $this->financialService->getCommissionChartData($booker);
        $topArtists = $this->financialService->getTopArtists($booker);
        $recentGigs = $this->financialService->getRecentGigs($booker);
        $analyticalTableData = ($startDate && $endDate) ? $this->financialService->getGigsForPeriod($booker, $startDate, $endDate) : collect();

        // 5. Renderiza uma NOVA view, específica para o portal
        return view('bookers.portal', [
            'booker' => $booker,
            'filters' => $filters,
            'salesKpis' => $salesKpis,
            'commissionKpis' => $commissionKpis,
            'chart' => $chart,
            'topArtists' => $topArtists,
            'recentGigs' => $recentGigs,
            'analyticalTableData' => $analyticalTableData,
        ]);
    }
}
