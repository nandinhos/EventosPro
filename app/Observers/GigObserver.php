<?php

namespace App\Observers;

use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class GigObserver
{
    protected GigFinancialCalculatorService $financialCalculator;

    public function __construct()
    {
        $this->financialCalculator = App::make(GigFinancialCalculatorService::class);
    }

    /**
     * Handle the Gig "saving" event.
     * Este observer agora verifica se os campos de comissão foram alterados
     * antes de reinterpretar os inputs, evitando recálculos incorretos
     * quando a Gig é salva por outras razões (ex: mudança de status).
     *
     * @param  \App\Models\Gig  $gig
     * @return void
     */
    public function saving(Gig $gig): void
    {
        Log::info("[GigObserver@saving] Iniciando para Gig ID: " . ($gig->id ?? 'NOVA GIG'));

        // Verifica se os campos de input de comissão foram alterados nesta requisição
        $isAgencyCommissionDirty = $gig->isDirty('agency_commission_type') || $gig->isDirty('agency_commission_value');
        $isBookerCommissionDirty = $gig->isDirty('booker_commission_type') || $gig->isDirty('booker_commission_value');

        // --- PREPARAÇÃO PARA COMISSÃO DA AGÊNCIA ---
        // SÓ executa esta lógica se os campos de comissão da agência vieram do formulário
        if ($isAgencyCommissionDirty) {
            Log::debug("[GigObserver@saving] Detectada alteração na comissão da agência. Reinterpretando inputs.");
            $agencyCommissionInputValue = $gig->agency_commission_value; // Valor/Taxa do input

            if (strtoupper($gig->agency_commission_type ?? '') === 'PERCENT') {
                // O valor do input é a TAXA. Salva em `rate`.
                $gig->agency_commission_rate = (float) $agencyCommissionInputValue;
            } else { // Tipo é FIXED
                // O valor do input é o VALOR FIXO. Limpa a taxa.
                $gig->agency_commission_rate = null;
            }
        }

        // --- PREPARAÇÃO PARA COMISSÃO DO BOOKER ---
        if ($isBookerCommissionDirty) {
            Log::debug("[GigObserver@saving] Detectada alteração na comissão do booker. Reinterpretando inputs.");
            $bookerCommissionInputValue = $gig->booker_commission_value;

            if ($gig->booker_id) {
                if (strtoupper($gig->booker_commission_type ?? '') === 'PERCENT') {
                    $gig->booker_commission_rate = (float) $bookerCommissionInputValue;
                } else { // Tipo é FIXED
                    $gig->booker_commission_rate = null;
                }
            } else {
                $gig->booker_commission_type = null;
                $gig->booker_commission_rate = null;
                $gig->booker_commission_value = null;
            }
        }

        // Após a preparação (se necessária), os campos de tipo e taxa no objeto $gig
        // estão corretos para o service usar. O service sempre será chamado para garantir
        // que os valores sejam consistentes, mesmo que só uma despesa tenha mudado.

        Log::debug("[GigObserver@saving] Dados da Gig antes de chamar o service:", [
            'id' => $gig->id ?? 'NOVA',
            'agency_type' => $gig->agency_commission_type,
            'agency_rate' => $gig->agency_commission_rate,
            'agency_value_pre_calc' => $gig->agency_commission_value,
            'booker_type' => $gig->booker_commission_type,
            'booker_rate' => $gig->booker_commission_rate,
            'booker_value_pre_calc' => $gig->booker_commission_value,
        ]);

        // Calcula os VALORES FINAIS EM BRL das comissões
        $calculatedAgencyGrossCommissionBrl = $this->financialCalculator->calculateAgencyGrossCommissionBrl($gig);
        $calculatedBookerCommissionBrl = $this->financialCalculator->calculateBookerCommissionBrl($gig);
        $calculatedAgencyNetCommissionBrl = $this->financialCalculator->calculateAgencyNetCommissionBrl($gig);

        // Atribui os valores CALCULADOS EM BRL aos campos para persistência
        $gig->agency_commission_value = $calculatedAgencyGrossCommissionBrl;
        $gig->booker_commission_value = $calculatedBookerCommissionBrl;
        $gig->liquid_commission_value = $calculatedAgencyNetCommissionBrl;

        Log::info("[GigObserver@saving] Gig ID: " . ($gig->id ?? 'NOVA') . " - VALORES FINAIS PARA SALVAR NO BANCO:", [
            'agency_commission_rate' => $gig->agency_commission_rate,
            'persisted_agency_commission_value_brl' => $gig->agency_commission_value,
            'booker_commission_rate' => $gig->booker_commission_rate,
            'persisted_booker_commission_value_brl' => $gig->booker_commission_value,
            'persisted_liquid_commission_value_brl' => $gig->liquid_commission_value,
        ]);
    }

    public function created(Gig $gig): void
    {
        Log::info("[GigObserver@created] Gig ID: {$gig->id} criada com sucesso.");
    }

    public function updated(Gig $gig): void
    {
        Log::info("[GigObserver@updated] Gig ID: {$gig->id} atualizada com sucesso.");
        if ($gig->wasChanged()) {
            Log::debug("[GigObserver@updated] Campos alterados: ", $gig->getChanges());
        }
    }

    public function deleted(Gig $gig): void
    {
        Log::info("[GigObserver@deleted] Gig ID: {$gig->id} excluída (soft delete).");
    }
}