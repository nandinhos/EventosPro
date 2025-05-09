<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGigCostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitir se o usuário pode atualizar a Gig pai, por exemplo
        // Ou simplesmente return true por enquanto.
        return true; // ou return $this->user()->can('update', $this->route('gig'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cost_center_id' => ['required', 'integer', 'exists:cost_centers,id'],
            'description'    => ['nullable', 'string', 'max:255'], // Alterado para nullable conforme form
            'value'          => ['required', 'numeric', 'min:0.01'],
            'currency'       => ['required', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GBP'])],
            'expense_date'   => ['nullable', 'date', 'before_or_equal:today'], // Despesa não pode ser no futuro
            'payer_type'     => ['required', 'string', Rule::in(['agencia', 'artista', 'cliente', 'outro'])],
            'payer_details'  => ['nullable', 'string', 'max:255', Rule::requiredIf($this->payer_type === 'outro')],
            'notes'          => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'cost_center_id.required' => 'O Centro de Custo é obrigatório.',
            'value.required' => 'O Valor da despesa é obrigatório.',
            'value.min' => 'O Valor da despesa deve ser positivo.',
            'currency.required' => 'A Moeda da despesa é obrigatória.',
            'payer_type.required' => 'O Pagador da despesa é obrigatório.',
            'payer_details.required_if' => 'Detalhes do Pagador são obrigatórios se o tipo for "Outro".',
            'expense_date.before_or_equal' => 'A Data da Despesa não pode ser futura.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Se a moeda não for BRL e não houver exchange_rate (não temos exchange_rate em GigCost)
        // ou se a moeda for BRL, garantir que exchange_rate (se existisse) fosse null.
        // No nosso caso, GigCost não tem exchange_rate, então não precisamos disso aqui.

        // Se description for obrigatório no form, não precisa tratar aqui.
        // Se description é opcional e vem vazio, o 'nullable' na regra cuida.
        if ($this->input('description') === null && ($this->route('cost') === null) ) { // Só default para novo
            $this->merge(['description' => 'Despesa Diversa']);
        }
    }
}