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
        Log::info("[GigObserver@saving] INICIANDO para Gig ID: " . ($gig->id ?? 'NOVA GIG'));
        Log::debug("[GigObserver@saving] Dados da Gig RECEBIDOS (do request/form) ANTES de qualquer lógica do observer:", [
            'id' => $gig->id ?? 'NOVA',
            'gig_original_attributes' => $gig->getOriginal(), // Valores do banco antes da mudança (se for update)
            'gig_dirty_attributes_from_request' => $gig->getDirty(), // Campos que vieram do request e são diferentes
            'agency_commission_type_from_request' => $gig->agency_commission_type,
            'agency_commission_value_from_request' => $gig->agency_commission_value, // Este é o input do form (taxa ou valor fixo)
            'booker_commission_type_from_request' => $gig->booker_commission_type,
            'booker_commission_value_from_request' => $gig->booker_commission_value, // Este é o input do form (taxa ou valor fixo)
        ]);

        // --- PREPARAÇÃO PARA COMISSÃO DA AGÊNCIA ---
        $agencyTypeFromRequest = strtoupper($gig->agency_commission_type ?? '') === 'PERCENT' ? 'PERCENT' : 'FIXED';
        // Se o tipo não for 'PERCENT', consideramos 'FIXED' ou o que estiver (o service trata fallback)
        // Se o campo vier vazio do form, o FormRequest deve ter setado um default ou o model.
        // Se for null, o service usará defaults internos.

        $agencyInputValueFromRequest = $gig->agency_commission_value; // Valor/Taxa do input

        // Limpa os campos rate/value no modelo $gig antes de popular com base no tipo
        // Isso garante que o service não pegue um 'rate' antigo se o tipo mudou para 'fixed', e vice-versa.
        $gig->agency_commission_rate = null;
        // Não zeramos $gig->agency_commission_value ainda, pois ele pode ser o valor fixo do input.

        if ($agencyTypeFromRequest === 'PERCENT') {
            $gig->agency_commission_rate = (float) $agencyInputValueFromRequest;
            // O campo $gig->agency_commission_value será preenchido pelo service com o valor BRL calculado.
            // Se $agencyInputValueFromRequest for null/vazio aqui, $gig->agency_commission_rate será 0.0,
            // e o service usará a taxa default (ex: 20%) se $gig->agency_commission_rate for nulo ou zero.
            // Se você quer que uma taxa vazia no form signifique "sem comissão percentual", o service precisa saber disso.
            // Por ora, o service tem um default se a taxa for nula.
        } else { // Tipo é FIXED
            // O $agencyInputValueFromRequest é o valor fixo.
            // O service usará $gig->agency_commission_value (que é o $agencyInputValueFromRequest)
            // e $gig->agency_commission_rate (que está null).
            // A linha $gig->agency_commission_value = $calculatedAgencyGrossCommissionBrl; abaixo
            // vai apenas reafirmar esse valor (já que para comissão fixa, o valor calculado é o próprio valor fixo).
        }
        // $gig->agency_commission_type já está correto vindo do request.

        Log::debug("[GigObserver@saving] Gig APÓS preparar inputs para Agência (para o Service):", [
            'agency_type_final' => $gig->agency_commission_type,
            'agency_rate_para_service' => $gig->agency_commission_rate,
            'agency_value_de_input_para_service' => ($agencyTypeFromRequest === 'FIXED') ? $agencyInputValueFromRequest : '(será calculado)',
        ]);

        // --- PREPARAÇÃO PARA COMISSÃO DO BOOKER ---
        $bookerTypeFromRequest = strtoupper($gig->booker_commission_type ?? '') === 'PERCENT' ? 'PERCENT' : 'FIXED';
        $bookerInputValueFromRequest = $gig->booker_commission_value;

        $gig->booker_commission_rate = null;

        if ($gig->booker_id) { // Só processa comissão do booker se houver um booker
            if ($bookerTypeFromRequest === 'PERCENT') {
                $gig->booker_commission_rate = (float) $bookerInputValueFromRequest;
            } else { // Tipo é FIXED
                // $bookerInputValueFromRequest é o valor fixo.
            }
        } else { // Sem booker, zera tudo
            $gig->booker_commission_type = null;
            $gig->booker_commission_rate = null;
            $gig->booker_commission_value = null; // Garante que o valor do input seja zerado se não houver booker
        }
        // $gig->booker_commission_type já está correto vindo do request (ou null se não houver booker).

        Log::debug("[GigObserver@saving] Gig APÓS preparar inputs para Booker (para o Service):", [
            'booker_id' => $gig->booker_id,
            'booker_type_final' => $gig->booker_commission_type,
            'booker_rate_para_service' => $gig->booker_commission_rate,
            'booker_value_de_input_para_service' => ($gig->booker_id && $bookerTypeFromRequest === 'FIXED') ? $bookerInputValueFromRequest : '(será calculado ou nulo)',
        ]);

        // Agora, o objeto $gig tem os atributos de tipo, taxa (se percentual),
        // e valor de input (se fixo) corretamente configurados para o service usar.

        // Chamar o Service para calcular os VALORES FINAIS EM BRL das comissões
        $calculatedAgencyGrossCommissionBrl = $this->financialCalculator->calculateAgencyGrossCommissionBrl($gig);
        $calculatedBookerCommissionBrl = $this->financialCalculator->calculateBookerCommissionBrl($gig); // Já retorna 0 se não houver booker_id
        $calculatedAgencyNetCommissionBrl = $this->financialCalculator->calculateAgencyNetCommissionBrl($gig);

        // Atribuir os valores CALCULADOS EM BRL aos campos para persistência
        // Estes campos no banco SEMPRE guardarão o valor monetário final em BRL da comissão.
        $gig->agency_commission_value = $calculatedAgencyGrossCommissionBrl;
        $gig->booker_commission_value = $calculatedBookerCommissionBrl; // Será 0 se não houver booker
        $gig->liquid_commission_value = $calculatedAgencyNetCommissionBrl;

        Log::info("[GigObserver@saving] Gig ID: " . ($gig->id ?? 'NOVA') . " - VALORES FINAIS PARA SALVAR NO BANCO:", [
            'agency_commission_type' => $gig->agency_commission_type, 'agency_commission_rate' => $gig->agency_commission_rate, 'persisted_agency_commission_value_brl' => $gig->agency_commission_value,
            'booker_commission_type' => $gig->booker_commission_type, 'booker_commission_rate' => $gig->booker_commission_rate, 'persisted_booker_commission_value_brl' => $gig->booker_commission_value,
            'persisted_liquid_commission_value_brl' => $gig->liquid_commission_value,
        ]);

        if ($gig->isDirty()) {
            Log::info("[GigObserver@saving] Alterações na Gig ID: " . ($gig->id ?? 'NOVA') . " prestes a serem salvas:", $gig->getDirty());
        } else {
            Log::info("[GigObserver@saving] Gig ID: " . ($gig->id ?? 'NOVA') . " - Nenhuma alteração suja detectada após processamento de comissões (pode ser que os valores calculados sejam iguais aos já existentes).");
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