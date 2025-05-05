<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log; // Manter para debug se precisar

class StoreGigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ou auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Não precisamos do $gigId aqui no Store

        return [
            'artist_id'               => ['required', 'integer', 'exists:artists,id'],
            'booker_id'               => ['nullable', 'integer', 'exists:bookers,id'],
            'gig_date'                => ['required', 'date'],
            'location_event_details'  => ['required', 'string', 'max:65535'],
            'cache_value'             => ['required', 'numeric', 'min:0'],
            'currency'                => ['required', 'string', 'max:10'],
            // 'exchange_rate'           => [ ... ], // REMOVIDO - Câmbio agora é por pagamento
            'expenses_value_brl'      => ['nullable', 'numeric', 'min:0'],
            'contract_number'         => ['nullable', 'string', 'max:100', 'unique:gigs,contract_number'], // Unique simples para store
            'contract_date'           => ['nullable', 'date', 'before_or_equal:gig_date'],
            'contract_status'         => ['nullable', 'string', 'max:50', Rule::in(['assinado', 'para_assinatura', 'expirado', 'n/a', 'cancelado'])],

            // Comissões - Tipos
            'agency_commission_type'  => ['nullable', 'string', Rule::in(['percent', 'fixed'])],
            'booker_commission_type'  => ['nullable', 'string', Rule::in(['percent', 'fixed'])],

            // Comissões - Valores (Validação base + condicional max:100 para percentual)
            'agency_commission_value' => ['nullable', 'numeric', 'min:0', Rule::when(fn() => $this->input('agency_commission_type') === 'percent', ['max:100'])],
            'booker_commission_value' => ['nullable', 'numeric', 'min:0', Rule::when(fn() => $this->input('booker_commission_type') === 'percent', ['max:100'])],

            // Notas
            'notes'                   => ['nullable', 'string', 'max:65535'],

            // Tags
            'tags'                    => ['nullable', 'array'],
            'tags.*'                  => ['integer', 'exists:tags,id'],
        ];
    }

     /**
      * Get custom messages for validator errors.
      */
    public function messages(): array
    {
        return [
            'artist_id.required' => 'Selecione o Artista.',
            'artist_id.exists' => 'Artista inválido.',
            'booker_id.exists' => 'Booker inválido.',
            'gig_date.required' => 'A Data do Evento é obrigatória.',
            'location_event_details.required' => 'A Localização/Detalhes do Evento é obrigatória.',
            'cache_value.required' => 'O Valor do Cachê Bruto é obrigatório.',
            'currency.required' => 'A Moeda é obrigatória.',
            // 'exchange_rate.required_if' => ..., // REMOVIDO
            'contract_number.unique' => 'Este Número de Contrato já existe.',
            'contract_date.before_or_equal' => 'A Data do Contrato não pode ser posterior à Data do Evento.',
            'tags.*.exists' => 'Uma das tags selecionadas é inválida.',
            'booker_commission_value.max' => 'A porcentagem da comissão não pode ser maior que 100.',
            'agency_commission_value.max' => 'A porcentagem da comissão não pode ser maior que 100.', // Adicionado msg agência
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $toMerge = [];

        if ($this->input('expenses_value_brl') === null || $this->input('expenses_value_brl') === '') {
             $toMerge['expenses_value_brl'] = 0.00;
        }
        if ($this->input('booker_id') === '') {
             $toMerge['booker_id'] = null;
        }
        if ($this->input('contract_status') === null || $this->input('contract_status') === '') {
            $toMerge['contract_status'] = 'n/a';
        }
        // REMOVIDO Bloco que tratava exchange_rate baseado na currency

        if (!empty($toMerge)) {
            $this->merge($toMerge);
        }
    }
}