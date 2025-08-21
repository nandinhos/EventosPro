<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateGigRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        // TODO: Adicionar Policy para verificar se o usuário pode atualizar esta Gig específica
        return true; // Por enquanto, permite
    }

    /**
     * Obtém as regras de validação que se aplicam à requisição.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        Log::debug('[UpdateGigRequest] Dados recebidos para validação (rules): ', $this->all());
        $gigId = $this->route('gig')?->id;

        return [
            'artist_id' => ['required', 'integer', 'exists:artists,id'],
            'booker_id' => ['nullable', 'integer', 'exists:bookers,id'],
            'gig_date' => ['required', 'date'],
            'location_event_details' => ['required', 'string', 'max:65535'],
            'cache_value' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GBP', 'GPB'])], // Moeda original (GPB é um alias para GBP)

            'contract_number' => ['nullable', 'string', 'max:100', Rule::unique('gigs', 'contract_number')->ignore($gigId)],
            'contract_date' => ['nullable', 'date', 'before_or_equal:gig_date'],
            'contract_status' => ['nullable', 'string', 'max:50', Rule::in(['assinado', 'para_assinatura', 'expirado', 'n/a', 'cancelado', 'concluido'])],

            'agency_commission_type' => ['nullable', 'string', Rule::in(['percent', 'fixed'])],
            'booker_commission_type' => ['nullable', 'string', Rule::in(['percent', 'fixed'])],

            'agency_commission_value' => ['nullable', 'numeric', 'min:0',
                Rule::when(fn () => $this->input('agency_commission_type') === 'percent', ['max:100']),
            ],
            'booker_commission_value' => ['nullable', 'numeric', 'min:0',
                Rule::when(fn () => $this->input('booker_commission_type') === 'percent', ['max:100']),
            ],

            'notes' => ['nullable', 'string', 'max:65535'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],

            // Despesas
            'expenses' => ['nullable', 'array'],
            'expenses.*.cost_center_id' => ['required_with:expenses', 'integer', 'exists:cost_centers,id'],
            'expenses.*.description' => ['nullable', 'string', 'max:255'],
            'expenses.*.value' => ['required_with:expenses', 'numeric', 'min:0.01'],
            'expenses.*.currency' => ['required_with:expenses', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GBP'])],
            'expenses.*.expense_date' => ['nullable', 'date'],
            'expenses.*.notes' => ['nullable', 'string', 'max:65535'],
            'expenses.*.is_confirmed' => ['nullable', 'boolean'],
            'expenses.*.is_invoice' => ['nullable', 'boolean'],
            'expenses.*.id' => ['nullable', 'integer', 'exists:gig_costs,id'], // Para edição/exclusão
            'expenses.*._deleted' => ['nullable', 'boolean'], // Para marcar exclusão
        ];
    }

    /**
     * Obtém mensagens personalizadas para erros de validação.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        // Pode copiar as mensagens do StoreGigRequest ou personalizá-las
        return [
            'artist_id.required' => 'O campo Artista é obrigatório.',
            'gig_date.required' => 'O campo Data do Evento é obrigatório.',
            'location_event_details.required' => 'O campo Local/Detalhes do Evento é obrigatório.',
            'cache_value.required' => 'O campo Cachê Bruto é obrigatório.',
            'currency.required' => 'O campo Moeda é obrigatória.',
            'contract_number.unique' => 'Este Número de Contrato já pertence a outra Gig.',
            'contract_date.before_or_equal' => 'A Data do Contrato não pode ser posterior à Data do Evento.',
            'agency_commission_value.max' => 'Se o tipo de comissão da agência for percentual, a taxa não pode ser maior que 100%.',
            'booker_commission_value.max' => 'Se o tipo de comissão do booker for percentual, a taxa não pode ser maior que 100%.',
            'expenses.*.cost_center_id.required_with' => 'O centro de custo é obrigatório para cada despesa.',
            'expenses.*.value.required_with' => 'O valor é obrigatório para cada despesa.',
            'expenses.*.currency.required_with' => 'A moeda é obrigatória para cada despesa.',
        ];
    }

    /**
     * Prepara os dados para validação.
     */
    protected function prepareForValidation(): void
    {
        // Lógica similar ao StoreGigRequest para defaults e limpeza
        $toMerge = [];

        if ($this->input('booker_id') === '') {
            $toMerge['booker_id'] = null;
        }
        if ($this->input('contract_status') === null || $this->input('contract_status') === '') {
            $toMerge['contract_status'] = 'n/a';
        }

        // Ao editar, se o tipo de comissão for 'percent', o 'agency_commission_value' do form
        // conterá a taxa. Se for 'fixed', conterá o valor fixo.
        // Não precisamos de muita manipulação aqui se o GigObserver/Service
        // já sabe interpretar isso com base no 'agency_commission_type'.

        // Converte os campos booleanos 'is_confirmed' e 'is_invoice' de despesas
        $expenses = $this->input('expenses', []);
        if (is_array($expenses)) {
            foreach ($expenses as $index => $expense) {
                $expenses[$index]['is_confirmed'] = filter_var($expense['is_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $expenses[$index]['is_invoice'] = filter_var($expense['is_invoice'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $expenses[$index]['_deleted'] = filter_var($expense['_deleted'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
            $toMerge['expenses'] = $expenses;
        }

        if (! empty($toMerge)) {
            $this->merge($toMerge);
        }
        Log::debug('[UpdateGigRequest] Dados após prepareForValidation: ', $this->all());
    }
}
