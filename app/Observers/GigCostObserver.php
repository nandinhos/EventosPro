<?php

namespace App\Observers;

use App\Models\GigCost;
use App\Models\Gig;
use Illuminate\Support\Facades\Log;

class GigCostObserver
{
    /**
     * Handle the GigCost "saved" event.
     * Este método é chamado após uma despesa ser salva ou atualizada.
     * Recalcula o cachê líquido e as comissões quando uma despesa é confirmada.
     */
    public function saved(GigCost $gigCost)
    {
        Log::info('GigCost saved event triggered', [
            'gig_cost_id' => $gigCost->id,
            'gig_id' => $gigCost->gig_id,
            'is_confirmed' => $gigCost->is_confirmed
        ]);

        // Busca a Gig associada
        $gig = Gig::find($gigCost->gig_id);
        if (!$gig) {
            Log::error('Gig não encontrada para o GigCost', ['gig_cost_id' => $gigCost->id]);
            return;
        }

        // Força o recálculo das comissões salvando a Gig
        // O GigObserver irá recalcular automaticamente os valores
        $gig->save();

        Log::info('Gig atualizada após alteração em GigCost', [
            'gig_id' => $gig->id,
            'gig_cost_id' => $gigCost->id
        ]);
    }
}