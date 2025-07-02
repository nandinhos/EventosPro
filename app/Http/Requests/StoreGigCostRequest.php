<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class StoreGigCostRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     * @return bool
     */
    public function authorize(): bool
    {
        // Idealmente, verificar se o usuário pode adicionar custos à Gig pai.
        // return $this->user()->can('update', $this->route('gig'));
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam à requisição.
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        Log::debug('[StoreGigCostRequest] Dados recebidos para validação: ', $this->all());
        return [
            'cost_center_id' => ['required', 'integer', 'exists:cost_centers,id'],
            'description'    => ['nullable', 'string', 'max:255'],
            'value'          => ['required', 'numeric', 'min:0.01'],
            'currency'       => ['required', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GBP'])],
            'expense_date'   => ['nullable', 'date'], // 'before_or_equal:today' pode ser muito restritivo se lançam despesas futuras previstas
            'notes'          => ['nullable', 'string', 'max:65535'],
            // 'is_confirmed' e 'is_invoice' serão tratados pelo controller/observer ao criar,
            // ou podem vir do form se você quiser permitir que o usuário defina na criação via modal.
            // Se vierem do form, adicione:
             'is_confirmed'   => ['sometimes', 'boolean'],
             'is_invoice'     => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Obtém mensagens personalizadas para erros de validação.
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cost_center_id.required' => 'O Centro de Custo é obrigatório.',
            'value.required' => 'O Valor da despesa é obrigatório.',
            'value.min' => 'O Valor da despesa deve ser positivo.',
            'currency.required' => 'A Moeda da despesa é obrigatória.',
            // 'expense_date.before_or_equal' => 'A Data da Despesa não pode ser futura.', // Removido se a regra permitir
        ];
    }

    /**
     * Prepara os dados para validação.
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Se description vier vazia e for uma nova despesa (não tem 'cost' na rota),
        // pode-se definir um default aqui se desejado.
        // No _form_fields_modal, já há um placeholder, mas não um valor default.
        // if ($this->input('description') === null && !$this->route('cost')) {
        //     $this->merge(['description' => 'Despesa Diversa']);
        // }

        // Se 'is_confirmed' ou 'is_invoice' vierem do formulário via checkbox,
        // eles podem não ser enviados se desmarcados. Trate-os aqui.
        // $this->merge([
        //     'is_confirmed' => $this->boolean('is_confirmed'),
        //     'is_invoice' => $this->boolean('is_invoice'),
        // ]);
        Log::debug('[StoreGigCostRequest] Dados após prepareForValidation: ', $this->all());
    }
}