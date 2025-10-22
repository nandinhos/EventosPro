<?php

namespace App\Observers;

use App\Models\Gig;
use App\Models\GigCost;
use App\Services\GigFinancialCalculatorService; // Importar o service
use Illuminate\Support\Facades\App;      // Para resolver o service
use Illuminate\Support\Facades\Log;

class GigCostObserver
{
    protected GigFinancialCalculatorService $financialCalculator;

    public function __construct()
    {
        // Injetar/resolver o service
        $this->financialCalculator = App::make(GigFinancialCalculatorService::class);
    }

    /**
     * Handle the GigCost "saved" event (created or updated).
     * Recalcula e atualiza apenas os campos de comissão da Gig pai.
     */
    public function saved(GigCost $gigCost): void
    {
        // Log::info("[GigCostObserver@saved] Acionado para GigCost ID: {$gigCost->id}, Gig ID: {$gigCost->gig_id}. Is Confirmed: ".($gigCost->is_confirmed ? 'Sim' : 'Não'));

        // Se a despesa foi revertida de confirmada para não confirmada, e estava marcada como NF, desmarca NF.
        if ($gigCost->wasChanged('is_confirmed') && ! $gigCost->is_confirmed && $gigCost->is_invoice) {
            $gigCost->is_invoice = false;
            $gigCost->saveQuietly(); // Salva o GigCost sem disparar observers novamente
            // Log::info("[GigCostObserver@saved] Marcação de NF removida do GigCost ID: {$gigCost->id} após reversão da confirmação.");
        }

        $gig = $gigCost->gig; // Pega a Gig pai

        if (! $gig) {
            Log::error("[GigCostObserver@saved] Gig não encontrada para o GigCost ID: {$gigCost->id}. Não foi possível recalcular comissões.");

            return;
        }

        // Garante que a Gig tenha os dados de tipo/taxa de comissão corretos antes de recalcular.
        // Os valores de tipo/taxa são definidos quando a Gig é salva via formulário.
        // Aqui, estamos apenas recalculando os valores monetários com base nesses tipos/taxas já existentes.

        // Calcula os novos valores de comissão usando o service
        $newAgencyGrossCommissionBrl = $this->financialCalculator->calculateAgencyGrossCommissionBrl($gig);
        $newBookerCommissionBrl = $this->financialCalculator->calculateBookerCommissionBrl($gig);
        $newAgencyNetCommissionBrl = $this->financialCalculator->calculateAgencyNetCommissionBrl($gig);

        // Verifica se algum valor de comissão realmente mudou para evitar updates desnecessários
        $comissionsChanged = false;
        if (abs((float) $gig->agency_commission_value - $newAgencyGrossCommissionBrl) > 0.001 ||
            abs((float) $gig->booker_commission_value - $newBookerCommissionBrl) > 0.001 ||
            abs((float) $gig->liquid_commission_value - $newAgencyNetCommissionBrl) > 0.001) {
            $comissionsChanged = true;
        }

        if ($comissionsChanged) {

            // ATUALIZA APENAS OS CAMPOS DE COMISSÃO, SEM DISPARAR OBSERVERS DA GIG NOVAMENTE
            $gig->forceFill([
                'agency_commission_value' => $newAgencyGrossCommissionBrl,
                'booker_commission_value' => $newBookerCommissionBrl,
                'liquid_commission_value' => $newAgencyNetCommissionBrl,
                // Importante: NÃO estamos alterando agency_commission_rate ou booker_commission_rate aqui,
                // pois eles são definidos pelo usuário no formulário da Gig. Apenas os valores BRL são recalculados.
            ])->saveQuietly(); // saveQuietly NÃO dispara eventos/observers do Eloquent

            // Log::info("[GigCostObserver@saved] Comissões da Gig ID: {$gig->id} atualizadas silenciosamente.");
        } else {
            // Log::info("[GigCostObserver@saved] Comissões da Gig ID: {$gig->id} não precisaram ser atualizadas após alteração no GigCost ID: {$gigCost->id}.");
        }

        // Disparar um evento específico se outras partes do sistema precisarem saber que as finanças da gig foram recalculadas
        // event(new GigFinancesRecalculated($gig));
    }

    /**
     * Handle the GigCost "deleted" event.
     * Também precisamos recalcular as comissões da Gig se um custo for removido.
     */
    public function deleted(GigCost $gigCost): void
    {
        // Log::info("[GigCostObserver@deleted] Acionado para GigCost ID: {$gigCost->id}, Gig ID: {$gigCost->gig_id}.");
        $gig = $gigCost->gig;
        if ($gig) {
            // A lógica de recálculo é a mesma do 'saved'
            $newAgencyGrossCommissionBrl = $this->financialCalculator->calculateAgencyGrossCommissionBrl($gig);
            $newBookerCommissionBrl = $this->financialCalculator->calculateBookerCommissionBrl($gig);
            $newAgencyNetCommissionBrl = $this->financialCalculator->calculateAgencyNetCommissionBrl($gig);

            if (abs((float) $gig->agency_commission_value - $newAgencyGrossCommissionBrl) > 0.001 ||
                abs((float) $gig->booker_commission_value - $newBookerCommissionBrl) > 0.001 ||
                abs((float) $gig->liquid_commission_value - $newAgencyNetCommissionBrl) > 0.001) {
                // Log::info("[GigCostObserver@deleted] Recalculando e atualizando comissões da Gig ID: {$gig->id} devido à exclusão do GigCost ID: {$gigCost->id}.");
                $gig->forceFill([
                    'agency_commission_value' => $newAgencyGrossCommissionBrl,
                    'booker_commission_value' => $newBookerCommissionBrl,
                    'liquid_commission_value' => $newAgencyNetCommissionBrl,
                ])->saveQuietly();
                // Log::info("[GigCostObserver@deleted] Comissões da Gig ID: {$gig->id} atualizadas silenciosamente.");
            }
        }
    }
}
