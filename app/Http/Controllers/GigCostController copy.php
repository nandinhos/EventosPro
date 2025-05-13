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
                return [
                    'cost_center' => $firstCost->costCenter ? // Verifica se costCenter existe
                                     ['id' => $firstCost->costCenter->id, 'name' => $firstCost->costCenter->name]
                                     : ['id' => null, 'name' => 'Desconhecido'],
                    'total_value' => $costsInGroup->sum('value'),
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
     * Confirma uma despesa.
     */
    public function confirm(Request $request, Gig $gig, GigCost $cost): JsonResponse
    {
        if ($cost->gig_id !== $gig->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }
        if ($cost->is_confirmed) {
             return response()->json(['message' => 'Esta despesa já está confirmada.'], 422);
        }

        try {
            $cost->update([
                'is_confirmed' => true,
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
            ]);
            // event(new GigDataChanged($gig));
            return response()->json(['message' => 'Despesa confirmada!', 'cost' => $cost->fresh()]);
        } catch (\Exception $e) {
            Log::error("Erro ao confirmar despesa {$cost->id}: " . $e->getMessage());
            return response()->json(['message' => 'Erro ao confirmar despesa.'], 500);
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
     * Somente despesas confirmadas podem ter seu status de nota fiscal alterado.
     */
    public function toggleInvoice(Request $request, Gig $gig, GigCost $cost): JsonResponse
    {
        if ($cost->gig_id !== $gig->id) {
            return response()->json(['message' => 'Acesso não autorizado.'], 403);
        }

        if (!$cost->is_confirmed) {
            return response()->json(['message' => 'Apenas despesas confirmadas podem ter o status de nota fiscal alterado.'], 422);
        }

        try {
            $cost->update([
                'is_invoice' => !$cost->is_invoice
            ]);

            $message = $cost->is_invoice
                ? 'Despesa marcada como nota fiscal!'
                : 'Status de nota fiscal removido da despesa!';

            return response()->json([
                'message' => $message,
                'cost' => $cost->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao alterar status de nota fiscal da despesa {$cost->id}: " . $e->getMessage());
            return response()->json(['message' => 'Erro ao alterar status de nota fiscal.'], 500);
        }
    }

    // Os métodos create e edit não são mais necessários se o form estiver em modal no show da Gig.
    
    /**
     * Show the form for creating a new gig cost for a specific gig.
     * Rota: gigs.costs.create (GET /gigs/{gig}/costs/create)
     */
    public function create(Gig $gig): View // Método CREATE
    {
        // Busca todos os centros de custo para popular o select no formulário
        $costCenters = CostCenter::orderBy('name')->pluck('name', 'id');

        // Passa a Gig pai e os centros de custo para a view do formulário
        // A view também precisará de um objeto GigCost vazio para o helper old()
        return view('gig_costs.create', [
            'gig' => $gig,
            'costCenters' => $costCenters,
            'cost' => new GigCost(['gig_id' => $gig->id, 'currency' => 'BRL', 'payer_type' => 'agencia']), // Preenche defaults
        ]);
    }
    
    // public function edit(Gig $gig, GigCost $cost): View { ... }
}