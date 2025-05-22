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
     * ANTES de uma Gig ser criada ou atualizada.
     *
     * @param  \App\Models\Gig  $gig
     * @return void
     */
    public function saving(Gig $gig): void
    {
        Log::info("[GigObserver@saving] Iniciando para Gig ID: " . ($gig->id ?? 'NOVA GIG'));

        // Captura os valores de INPUT que vieram do formulário para comissões.
        // $gig->agency_commission_value e $gig->booker_commission_value contêm
        // a TAXA (se tipo percentual) ou o VALOR FIXO (se tipo fixo) digitado pelo usuário.
        $agencyCommissionInputValue = $gig->agency_commission_value;
        $bookerCommissionInputValue = $gig->booker_commission_value;

        // Prepara os atributos da Gig para o Service
        if (strtoupper($gig->agency_commission_type ?? '') === 'PERCENT') {
            $gig->agency_commission_rate = (float) $agencyCommissionInputValue;
             // $gig->agency_commission_value será sobrescrito pelo valor BRL calculado
        } else { // FIXED ou não definido
            $gig->agency_commission_rate = null;
            // $gig->agency_commission_value já contém o valor fixo do input para o service usar
        }

        if (strtoupper($gig->booker_commission_type ?? '') === 'PERCENT') {
            $gig->booker_commission_rate = (float) $bookerCommissionInputValue;
        } else { // FIXED ou não definido
            $gig->booker_commission_rate = null;
        }

        Log::debug("[GigObserver@saving] Gig APÓS preparar rates/fixed_values para o Service:", [
            'id' => $gig->id ?? 'NOVA',
            'agency_type' => $gig->agency_commission_type,
            'agency_rate_para_service' => $gig->agency_commission_rate,
            'agency_fixed_value_para_service' => (strtoupper($gig->agency_commission_type ?? '') !== 'PERCENT') ? $agencyCommissionInputValue : null,
            'booker_type' => $gig->booker_commission_type,
            'booker_rate_para_service' => $gig->booker_commission_rate,
            'booker_fixed_value_para_service' => (strtoupper($gig->booker_commission_type ?? '') !== 'PERCENT') ? $bookerCommissionInputValue : null,
        ]);

        // Chama o Service para calcular os VALORES FINAIS EM BRL das comissões
        // O Service usará os $gig->agency_commission_rate, $gig->booker_commission_rate,
        // $gig->agency_commission_value (como fixo de input), $gig->booker_commission_value (como fixo de input)
        // que acabamos de preparar.
        $calculatedAgencyGrossCommissionBrl = $this->financialCalculator->calculateAgencyGrossCommissionBrl($gig);
        $calculatedBookerCommissionBrl = $this->financialCalculator->calculateBookerCommissionBrl($gig);
        $calculatedAgencyNetCommissionBrl = $this->financialCalculator->calculateAgencyNetCommissionBrl($gig);

        // Atribui os valores CALCULADOS EM BRL aos campos para persistência
        $gig->agency_commission_value = $calculatedAgencyGrossCommissionBrl;
        $gig->booker_commission_value = $calculatedBookerCommissionBrl;
        $gig->liquid_commission_value = $calculatedAgencyNetCommissionBrl;

        Log::info("[GigObserver@saving] Gig ID: " . ($gig->id ?? 'NOVA') .
            " - Com. Ag. Bruta BRL (Salvar): {$gig->agency_commission_value}");
        Log::info("[GigObserver@saving] Gig ID: " . ($gig->id ?? 'NOVA') .
            " - Com. Booker BRL (Salvar): {$gig->booker_commission_value}");
        Log::info("[GigObserver@saving] Gig ID: " . ($gig->id ?? 'NOVA') .
            " - Com. Líquida Ag. BRL (Salvar): {$gig->liquid_commission_value}");

        if ($gig->isDirty()) {
            Log::info("[GigObserver@saving] Alterações na Gig ID: " . ($gig->id ?? 'NOVA') . " prestes a serem salvas:", $gig->getDirty());
        }
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