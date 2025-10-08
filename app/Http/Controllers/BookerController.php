<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookerRequest;
use App\Http\Requests\UpdateBookerRequest;
use App\Models\Booker;
use App\Models\Gig;
use App\Services\BookerFinancialsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        // Novos dados para agrupamentos de eventos realizados e futuros
        $realizedEvents = $this->financialService->getRealizedEvents($booker, $startDate, $endDate);
        $futureEvents = $this->financialService->getFutureEvents($booker, $startDate, $endDate);

        return view('bookers.show', [
            'booker' => $booker,
            'filters' => $filters,
            'salesKpis' => $salesKpis,
            'commissionKpis' => $commissionKpis,
            'chart' => $chart,
            'topArtists' => $topArtists,
            'recentGigs' => $recentGigs,
            'analyticalTableData' => $analyticalTableData,
            'realizedEvents' => $realizedEvents,
            'futureEvents' => $futureEvents,
        ]);
    }

    /**
     * Atualiza comissão de um evento específico
     */
    public function updateEventCommission(Request $request, $eventId)
    {
        try {
            $gig = Gig::findOrFail($eventId);

            // Verificar se o usuário tem permissão para editar este evento
            $user = Auth::user();
            $booker = $user->booker;

            if (! $booker || $gig->booker_id !== $booker->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este evento.',
                ], 403);
            }

            $validated = $request->validate([
                'booker_payment_status' => 'required|in:pendente,pago,cancelado',
                'booker_commission_brl' => 'required|numeric|min:0',
                'is_exception' => 'boolean',
                'exception_notes' => 'nullable|string|max:1000',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Aplicar regra de negócio: não permitir pagamento para eventos não realizados
            $isEventRealized = $gig->gig_date <= now();
            $isException = $request->boolean('is_exception');

            if (! $isEventRealized && $validated['booker_payment_status'] === 'pago' && ! $isException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível marcar como pago um evento que ainda não foi realizado. Marque como exceção se necessário.',
                ], 422);
            }

            // Atualizar dados do evento
            $gig->booker_payment_status = $validated['booker_payment_status'];

            // Atualizar comissão se fornecida
            if (isset($validated['booker_commission_brl'])) {
                $gig->booker_commission_brl = $validated['booker_commission_brl'];
            }

            // Gerenciar exceções usando campos de observação existentes
            if ($isException && ! $isEventRealized) {
                $exceptionNote = 'EXCEÇÃO JUSTIFICADA: '.($validated['exception_notes'] ?? 'Sem justificativa fornecida');
                $gig->booker_notes = $exceptionNote;
            }

            // Adicionar observações gerais
            if (! empty($validated['notes'])) {
                $currentNotes = $gig->notes ?? '';
                $newNote = '['.now()->format('d/m/Y H:i').'] '.$validated['notes'];
                $gig->notes = $currentNotes ? $currentNotes."\n".$newNote : $newNote;
            }

            $gig->save();

            return response()->json([
                'success' => true,
                'message' => 'Comissão atualizada com sucesso!',
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao atualizar comissão do evento: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente.',
            ], 500);
        }
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
