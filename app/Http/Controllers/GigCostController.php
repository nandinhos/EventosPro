<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Models\GigCost;
use App\Models\CostCenter;
use App\Http\Requests\StoreGigCostRequest; // Certifique-se de ter este Form Request
use App\Http\Requests\UpdateGigCostRequest; // Certifique-se de ter este Form Request
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

class GigCostController extends Controller
{
    /**
     * Retorna os custos de uma Gig agrupados por centro de custo em formato JSON.
     * Usado pelo Alpine.js para popular a tabela.
     */
    public function listJson(Gig $gig): JsonResponse
    {
        $costsByCenter = $gig->costs()
            ->with(['costCenter', 'confirmer']) // Carrega quem confirmou também
            ->orderBy('expense_date', 'desc') // Ordena custos dentro do grupo
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('cost_center_id')
            ->map(function ($costsInGroup, $costCenterId) {
                $firstCost = $costsInGroup->first();
                $translatedName = $firstCost->costCenter ? __('cost_centers.' . $firstCost->costCenter->name) : 'Desconhecido';
                return [
                    'cost_center' => $firstCost->costCenter ?
                                     ['id' => $firstCost->costCenter->id, 'name' => $translatedName] : ['id' => null, 'name' => 'Desconhecido'],
                    'total_value' => $costsInGroup->sum('value_brl'),
                    'count' => $costsInGroup->count(),
                    'costs' => $costsInGroup->map(function ($cost) {
                        return [
                            'id' => $cost->id,
                            'cost_center_id' => $cost->cost_center_id,
                            'description' => $cost->description,
                            'value' => $cost->value,
                            'currency' => $cost->currency,
                            'expense_date_formatted' => $cost->expense_date ? $cost->expense_date->format('d/m/Y') : 'N/A',
                            'expense_date' => $cost->expense_date?->format('Y-m-d'), // Para formulários
                            'is_confirmed' => $cost->is_confirmed,
                            'is_invoice' => $cost->is_invoice,
                            'confirmed_at_formatted' => $cost->confirmed_at ? $cost->confirmed_at->format('d/m/y H:i') : null,
                            'confirmed_by_name' => $cost->confirmer?->name, // Usa o relacionamento 'confirmer'
                            'notes' => $cost->notes,
                            'payer_type' => $cost->payer_type,
                            'payer_details' => $cost->payer_details,
                        ];
                    })
                ];
            })->values(); // Retorna como array indexado

        return response()->json($costsByCenter);
    }

    /**
     * Armazena uma nova despesa para a Gig.
     */
    public function store(StoreGigCostRequest $request, Gig $gig): JsonResponse // Retorna JSON para fetch
    {
        try {
            $data = $request->validated();
            $data['gig_id'] = $gig->id;
            $data['is_confirmed'] = false; // Novas despesas iniciam não confirmadas
            $data['confirmed_by'] = null;
            $data['confirmed_at'] = null;

            $cost = GigCost::create($data);

            // Disparar evento para recalcular totais da Gig, se necessário
            // event(new GigDataChanged($gig));

            return response()->json(['message' => 'Despesa adicionada com sucesso!', 'cost' => $cost], 201);
        } catch (\Exception $e) {
            Log::error("Erro ao adicionar despesa à Gig {$gig->id}: " . $e->getMessage());
            return response()->json(['message' => 'Erro ao adicionar despesa.'], 500);
        }
    }

    /**
     * Atualiza uma despesa existente.
     */
    public function update(UpdateGigCostRequest $request, Gig $gig, GigCost $cost): JsonResponse
    {
        if ($cost->gig_id !== $gig->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }
        if ($cost->is_confirmed && !$request->user()->can('edit_confirmed_costs')) { // Exemplo de Policy
            return response()->json(['message' => 'Despesas confirmadas não podem ser editadas por você.'], 403);
        }

        try {
            $cost->update($request->validated());
            // event(new GigDataChanged($gig));
            return response()->json(['message' => 'Despesa atualizada com sucesso!', 'cost' => $cost->fresh()]);
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar despesa {$cost->id}: " . $e->getMessage());
            return response()->json(['message' => 'Erro ao atualizar despesa.'], 500);
        }
    }

    /**
     * Remove uma despesa (Soft Delete).
     */
    public function destroy(Gig $gig, GigCost $cost): JsonResponse
    {
        if ($cost->gig_id !== $gig->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }
        if ($cost->is_confirmed && !$request->user()->can('delete_confirmed_costs')) { // Exemplo de Policy
            return response()->json(['message' => 'Despesas confirmadas não podem ser excluídas por você.'], 403);
        }

        try {
            $cost->delete();
            // event(new GigDataChanged($gig));
            return response()->json(['message' => 'Despesa removida com sucesso!']);
        } catch (\Exception $e) {
            Log::error("Erro ao remover despesa {$cost->id}: " . $e->getMessage());
            return response()->json(['message' => 'Erro ao remover despesa.'], 500);
        }
    }

    /**
     * Confirma uma despesa, usando uma data informada pelo usuário.
     */
    public function confirm(Request $request, Gig $gig, GigCost $cost): JsonResponse
    {
        if ($cost->gig_id !== $gig->id || $cost->is_confirmed) {
            return response()->json(['message' => 'Esta despesa não pode ser confirmada.'], 422);
        }

        // Valida a data de confirmação vinda do formulário inline
        $validated = $request->validate([
            'confirmed_at_date' => ['required', 'date', 'before_or_equal:today']
        ], [
            'confirmed_at_date.required' => 'A data de confirmação é obrigatória.',
            'confirmed_at_date.before_or_equal' => 'A data de confirmação não pode ser no futuro.',
        ]);

        try {
            $cost->update([
                'is_confirmed' => true,
                'confirmed_by' => Auth::id(),
                'confirmed_at' => Carbon::parse($validated['confirmed_at_date'])->endOfDay(), // Usa a data do form
            ]);
            
            // Retorna a mensagem de sucesso e o custo atualizado para o Alpine
            return response()->json([
                'message' => 'Despesa confirmada com sucesso!',
                'cost' => $cost->fresh()->load('confirmer', 'costCenter')
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao confirmar despesa {$cost->id}: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno ao confirmar despesa.'], 500);
        }
    }

    /**
     * Remove a confirmação de uma despesa.
     */
    public function unconfirm(Request $request, Gig $gig, GigCost $cost): JsonResponse
    {
        if ($cost->gig_id !== $gig->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }
        if (!$cost->is_confirmed) {
            return response()->json(['message' => 'Esta despesa não estava confirmada.'], 422);
        }
        // TODO: Adicionar Policy para verificar se o usuário pode desconfirmar

        try {
            $cost->update([
                'is_confirmed' => false,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);
            // event(new GigDataChanged($gig));
            return response()->json(['message' => 'Confirmação da despesa revertida!', 'cost' => $cost->fresh()]);
        } catch (\Exception $e) {
            Log::error("Erro ao desconfirmar despesa {$cost->id}: " . $e->getMessage());
            return response()->json(['message' => 'Erro ao desconfirmar despesa.'], 500);
        }
    }

    /**
     * Alterna o status de nota fiscal (is_invoice) de uma despesa.
     * Rota: PATCH gigs/{gig}/costs/{cost}/toggle-invoice (gigs.costs.toggleInvoice)
     */
    public function toggleInvoice(Request $request, Gig $gig, GigCost $cost): JsonResponse
    {
        if ($cost->gig_id !== $gig->id) { return response()->json(['message' => 'Acesso não autorizado.'], 403); }
        // if (!$cost->is_confirmed) { return response()->json(['message' => 'Apenas despesas confirmadas...'], 422); } // MANTENHA SE QUISER

        try {
            $cost->update(['is_invoice' => !$cost->is_invoice]);
            $message = $cost->is_invoice ? 'Despesa marcada como inclusa na NF!' : 'Marcação de NF removida da despesa!';
            return response()->json(['message' => $message, 'cost' => $cost->fresh()->load('costCenter', 'confirmer')]);
        } catch (\Exception $e) { /* ... */ }
    }

    // Os métodos create e edit não são mais necessários se o form estiver em modal no show da Gig.
    
    /**
     * Show the form for creating a new gig cost for a specific gig.
     * Rota: gigs.costs.create (GET /gigs/{gig}/costs/create)
     */
    public function create(Gig $gig): View
    {
        $costCenters = CostCenter::orderBy('name')->pluck('name', 'id');
        $cost = new GigCost(['gig_id' => $gig->id, 'currency' => 'BRL', 'payer_type' => 'agencia', 'expense_date' => today()]);
        // A view _form_modal.blade.php espera $costCenters e $cost (e $gig implicitamente pela rota)
        return view('gig_costs.create', compact('gig', 'costCenters', 'cost'));
    }
    
    // public function edit(Gig $gig, GigCost $cost): View { ... }
}