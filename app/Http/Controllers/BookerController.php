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

class BookerController extends Controller
{
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

    /** Display the specified resource. */
    public function show(Booker $booker, Request $request): View
    {
        // Query base para as gigs do booker
        $gigsQuery = $booker->gigs()->with('artist'); // Carrega artista

        // --- Métricas ATUALIZADAS ---
        $totalGigs = $gigsQuery->clone()->count(); // Clonar para não afetar a query principal

        // Soma da Comissão do Booker onde o status de pagamento da comissão é 'pago'
        $commissionReceived = $gigsQuery->clone()
                                        ->where('booker_payment_status', 'pago')
                                        ->sum('booker_commission_value'); // Soma o valor salvo

        // Soma da Comissão do Booker onde o status de pagamento da comissão é 'pendente'
        $commissionPending = $gigsQuery->clone()
                                       ->where('booker_payment_status', 'pendente')
                                       ->sum('booker_commission_value'); // Soma o valor salvo


        // --- Filtragem e Paginação das Gigs (sem alterações) ---
        if ($request->filled('gig_status')) { $gigsQuery->where('payment_status', $request->input('gig_status')); }
        // ... (outros filtros) ...
        $gigs = $gigsQuery->latest('gig_date')->paginate(15)->withQueryString();

        // Dados para os cards ATUALIZADOS
        $metrics = [
            'total_gigs' => $totalGigs,
            'commission_received_brl' => $commissionReceived ?? 0, // Garante que é numérico
            'commission_pending_brl' => $commissionPending ?? 0, // Garante que é numérico
        ];

        $artists = Artist::orderBy('name')->pluck('name', 'id');

        return view('bookers.show', compact('booker', 'gigs', 'metrics', 'artists'));
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