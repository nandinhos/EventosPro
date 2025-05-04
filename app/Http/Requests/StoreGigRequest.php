<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Importar Rule

class StoreGigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Permite que qualquer usuário logado crie uma Gig por enquanto.
     * Pode ser ajustado com Policies depois.
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
        // Pega o ID da Gig da rota (será null no StoreRequest, não tem problema)
        // Usamos optional chaining (?->) para evitar erro no StoreRequest
        $gigId = $this->route('gig')?->id;

        return [
            'artist_id'               => ['required', 'integer', 'exists:artists,id'],
            'booker_id'               => ['nullable', 'integer', 'exists:bookers,id'],
            'gig_date'                => ['required', 'date'],
            'location_event_details'  => ['required', 'string', 'max:65535'],
            'cache_value'             => ['required', 'numeric', 'min:0'],
            'currency'                => ['required', 'string', 'max:10'],
            'exchange_rate'           => [
                                            'nullable',
                                            Rule::requiredIf(fn() => strtoupper($this->input('currency', 'BRL')) !== 'BRL'), // Acessa input
                                            'numeric',
                                            'min:0'
                                         ],
            'expenses_value_brl'      => ['nullable', 'numeric', 'min:0'],
            'contract_number'         => [
                                            'nullable',
                                            'string',
                                            'max:100',
                                            // Aplica unique ignorando o ID atual (só relevante no UpdateRequest)
                                            Rule::unique('gigs', 'contract_number')->ignore($gigId)
                                         ],
            'contract_date'           => ['nullable', 'date', 'before_or_equal:gig_date'],
            'contract_status'         => ['nullable', 'string', 'max:50', Rule::in(['assinado', 'para_assinatura', 'expirado', 'n/a', 'cancelado'])],

            // Comissões - Tipos
            'agency_commission_type'  => ['nullable', 'string', Rule::in(['percent', 'fixed'])],
            'booker_commission_type'  => ['nullable', 'string', Rule::in(['percent', 'fixed'])],

            // Comissões - Valores (Validação base + condicional de max 100 para percentual)
            'agency_commission_value' => [
                                            'nullable',
                                            'numeric',
                                            'min:0',
                                            // Só aplica max:100 se o tipo for 'percent'
                                            Rule::when(fn() => $this->input('agency_commission_type') === 'percent', ['max:100'])
                                         ],
            'booker_commission_value' => [
                                            'nullable',
                                            'numeric',
                                            'min:0',
                                            // Só aplica max:100 se o tipo for 'percent'
                                            Rule::when(fn() => $this->input('booker_commission_type') === 'percent', ['max:100'])
                                         ],

            // Notas
            'notes'                   => ['nullable', 'string', 'max:65535'],

            // Tags
            'tags'                    => ['nullable', 'array'],
            'tags.*'                  => ['integer', 'exists:tags,id'], // Valida cada ID no array
        ];
    }

     /**
      * Get custom messages for validator errors.
      * (Opcional)
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
            'exchange_rate.required_if' => 'A Taxa de Câmbio é obrigatória quando a moeda não é BRL.',
            'contract_number.unique' => 'Este Número de Contrato já existe.',
            'contract_date.before_or_equal' => 'A Data do Contrato não pode ser posterior à Data do Evento.',
            'tags.*.exists' => 'Uma das tags selecionadas é inválida.',
            'booker_commission_value.max' => 'A porcentagem da comissão não pode ser maior que 100.',
            // Adicione outras mensagens personalizadas se desejar
        ];
    }

    /**
     * Prepare the data for validation.
     * Limpa/Modifica dados antes da validação ser executada.
     */
    protected function prepareForValidation(): void
    {
        $toMerge = [];

        // Garante que expenses_value_brl seja 0.00 se enviado vazio ou nulo
        if ($this->input('expenses_value_brl') === null || $this->input('expenses_value_brl') === '') {
             $toMerge['expenses_value_brl'] = 0.00;
        }

        // Garante que booker_id seja null se a opção vazia ("") for enviada
        if ($this->input('booker_id') === '') {
             $toMerge['booker_id'] = null;
        }

        // Garante que contract_status seja 'n/a' se enviado vazio ou nulo
        // (Ajuste 'n/a' se o default desejado for outro)
        if ($this->input('contract_status') === null || $this->input('contract_status') === '') {
            $toMerge['contract_status'] = 'n/a';
        }

        // Garante que exchange_rate seja null e removido se a moeda for BRL
        if (strtoupper($this->input('currency', 'BRL')) === 'BRL') {
             $toMerge['exchange_rate'] = null;
        }

        // Mescla os dados preparados de volta na requisição
        if (!empty($toMerge)) {
            $this->merge($toMerge);
        }

        // Opcional: Log para debug (remover em produção)
        // \Illuminate\Support\Facades\Log::debug('Data prepared for validation:', $this->all());
    }
}