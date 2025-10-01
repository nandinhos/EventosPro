<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class GigFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Esta função pode ser sobrescrita nas classes filhas se a autorização for diferente.
     */
    public function authorize(): bool
    {
        return true; // Default, pode ser sobrescrito. Idealmente, use Policies.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $gigId = $this->route('gig')?->id; // Null para Store, ID para Update

        return [
            'artist_id' => ['required', 'integer', 'exists:artists,id'],
            'booker_id' => ['nullable', 'integer', 'exists:bookers,id'],
            'gig_date' => ['required', 'date'],
            'location_event_details' => ['required', 'string', 'max:65535'],

            'cache_value' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GPB'])], // Adicionado GBP
            // Campo exchange_rate removido - não é mais necessário
            // 'expenses_value_brl' foi removido, as despesas são tratadas como array 'expenses'

            'contract_number' => [
                'nullable',
                'string',
                'max:100',
                // Unique rule é diferente para store e update, será ajustada nas classes filhas
                Rule::unique('gigs', 'contract_number')->ignore($gigId),
            ],
            'contract_date' => ['nullable', 'date', 'before_or_equal:gig_date'],
            'contract_status' => ['nullable', 'string', 'max:50', Rule::in(['assinado', 'para_assinatura', 'expirado', 'n/a', 'cancelado'])],

            // Comissões - Tipos
            'agency_commission_type' => ['nullable', 'string', Rule::in(['percent', 'fixed'])],
            'booker_commission_type' => ['nullable', 'string', Rule::in(['percent', 'fixed', ''])], // Permitir string vazia para "Nenhuma"

            // Comissões - Valores/Taxas (O campo do form é sempre 'agency_commission_value' e 'booker_commission_value')
            'agency_commission_value' => ['nullable', 'numeric', 'min:0',
                Rule::when(fn () => $this->input('agency_commission_type') === 'percent', ['max:100']),
            ],
            'booker_commission_value' => ['nullable', 'numeric', 'min:0',
                Rule::when(fn () => $this->input('booker_commission_type') === 'percent', ['max:100']),
            ],

            'notes' => ['nullable', 'string', 'max:65535'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],

            // Validação para o array de despesas
            'expenses' => ['nullable', 'array'],
            'expenses.*.cost_center_id' => ['required_with:expenses.*.value', 'nullable', 'integer', 'exists:cost_centers,id'],
            'expenses.*.description' => ['nullable', 'string', 'max:255'],
            'expenses.*.value' => ['required_with:expenses.*.cost_center_id', 'nullable', 'numeric', 'min:0.01'],
            'expenses.*.currency' => ['nullable', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GPB'])],
            'expenses.*.expense_date' => ['nullable', 'date'],
            'expenses.*.notes' => ['nullable', 'string', 'max:65535'],
            'expenses.*.is_confirmed' => ['nullable', 'boolean'],
            'expenses.*.is_invoice' => ['nullable', 'boolean'],
            'expenses.*.id' => ['nullable', 'integer', 'exists:gig_costs,id'], // Para update de despesas existentes
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
            'exchange_rate.required_if' => 'A Taxa de Câmbio é obrigatória quando a moeda não é BRL.',
            'contract_number.unique' => 'Este Número de Contrato já existe.',
            'contract_date.before_or_equal' => 'A Data do Contrato não pode ser posterior à Data do Evento.',
            'tags.*.exists' => 'Uma das tags selecionadas é inválida.',

            'agency_commission_type.in' => 'O tipo de comissão da agência selecionado é inválido.',
            'booker_commission_type.in' => 'O tipo de comissão do booker selecionado é inválido.',
            'agency_commission_value.max' => 'A porcentagem da comissão da agência não pode ser maior que 100.',
            'booker_commission_value.max' => 'A porcentagem da comissão do booker não pode ser maior que 100.',

            'expenses.*.cost_center_id.required_with' => 'O centro de custo da despesa #:position é obrigatório se um valor foi fornecido.',
            'expenses.*.value.required_with' => 'O valor da despesa #:position é obrigatório se um centro de custo foi fornecido.',
            'expenses.*.value.min' => 'O valor da despesa #:position deve ser positivo.',
        ];
    }

    /**
     * Prepare the data for validation.
     * Limpa/Modifica dados antes da validação ser executada.
     */
    protected function prepareForValidation(): void
    {
        $toMerge = [];

        if ($this->input('booker_id') === '') {
            $toMerge['booker_id'] = null;
        }
        if (! $this->filled('contract_status')) { // Se não preenchido ou string vazia
            $toMerge['contract_status'] = 'n/a';
        }

        // Garante que exchange_rate seja null se a moeda for BRL ou se não for fornecido
        if (strtoupper($this->input('currency', 'BRL')) === 'BRL' || ! $this->filled('exchange_rate')) {
            $toMerge['exchange_rate'] = null;
        }

        // Tratar tipos de comissão
        if (! $this->filled('agency_commission_type')) {
            $toMerge['agency_commission_type'] = 'percent'; // Default
        }

        // Se booker_commission_type for string vazia (de "Nenhuma"), converte para null
        // para que 'nullable' funcione corretamente e 'Rule::in' não reclame.
        if ($this->input('booker_commission_type') === '') {
            $toMerge['booker_commission_type'] = null;
        } elseif (! $this->filled('booker_commission_type') && $this->filled('booker_id')) {
            // Se tem booker_id, mas o tipo não foi enviado, assume percentual.
            // Se booker_id não estiver preenchido, tipo de comissão do booker pode ser null.
            $toMerge['booker_commission_type'] = 'percent';
        } elseif (! $this->filled('booker_commission_type') && ! $this->filled('booker_id')) {
            $toMerge['booker_commission_type'] = null;
        }

        // Limpar valores de comissão se os tipos não exigirem
        if ($this->input('agency_commission_type') === 'percent' && $this->input('agency_commission_value') === null) {
            // Se tipo é percentual e valor está nulo, pode ser o caso de um default de taxa
            // $toMerge['agency_commission_value'] = 20.00; // Ou pegue de $existingGig se editando
        } elseif ($this->input('agency_commission_type') === 'fixed' && $this->input('agency_commission_value') === null) {
            $toMerge['agency_commission_value'] = 0.00;
        }

        if ($this->input('booker_commission_type') === 'percent' && $this->input('booker_commission_value') === null && $this->filled('booker_id')) {
            // $toMerge['booker_commission_value'] = 5.00;
        } elseif ($this->input('booker_commission_type') === 'fixed' && $this->input('booker_commission_value') === null && $this->filled('booker_id')) {
            $toMerge['booker_commission_value'] = 0.00;
        } elseif ($this->input('booker_commission_type') === null || $this->input('booker_commission_type') === '') {
            // Se o tipo for "Nenhuma" ou nulo, o valor/taxa também deve ser nulo
            $toMerge['booker_commission_value'] = null;
        }

        // Preparar despesas: garantir que is_confirmed e is_invoice sejam booleanos
        if ($this->has('expenses')) {
            $expenses = $this->input('expenses');
            foreach ($expenses as $index => $expenseData) {
                $expenses[$index]['is_confirmed'] = filter_var($expenseData['is_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $expenses[$index]['is_invoice'] = filter_var($expenseData['is_invoice'] ?? false, FILTER_VALIDATE_BOOLEAN);
                // Se o valor da despesa não for fornecido, mas o centro de custo sim, a validação 'required_with' vai pegar.
                // Se o centro de custo não for fornecido, a despesa pode ser ignorada no backend.
            }
            $toMerge['expenses'] = $expenses;
        }

        if (! empty($toMerge)) {
            $this->merge($toMerge);
        }

        Log::debug('GigFormRequest - Data after prepareForValidation:', $this->all());
    }
}
