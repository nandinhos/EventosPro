<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UpdateGigCostRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     * @return bool
     */
    public function authorize(): bool
    {
        // return $this->user()->can('update', $this->route('cost'));
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam à requisição.
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Usar 'sometimes' para permitir atualizações parciais
        Log::debug('[UpdateGigCostRequest] Dados recebidos para validação: ', $this->all());
        return [
            'cost_center_id' => ['sometimes', 'required', 'integer', 'exists:cost_centers,id'],
            'description'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'value'          => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'currency'       => ['sometimes', 'required', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GBP'])],
            'expense_date'   => ['sometimes', 'nullable', 'date'],
            'notes'          => ['sometimes', 'nullable', 'string', 'max:65535'],
            // 'is_confirmed' e 'is_invoice' não são tipicamente atualizados via form de edição de dados,
            // mas sim por ações específicas (confirmar, marcar como NF). Se forem, adicione 'sometimes' e 'boolean'.
        ];
    }

    /**
     * Obtém mensagens personalizadas para erros de validação.
     * @return array<string, string>
     */
    public function messages(): array
    {
        // Mesmas mensagens do StoreGigCostRequest
        return [
            'cost_center_id.required' => 'O Centro de Custo é obrigatório.',
            'value.required' => 'O Valor da despesa é obrigatório.',
            'value.min' => 'O Valor da despesa deve ser positivo.',
            'currency.required' => 'A Moeda da despesa é obrigatória.',
        ];
    }

    /**
     * Prepara os dados para validação.
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Similar ao Store, se precisar tratar booleans que vêm do form
        // $this->merge([
        //     'is_confirmed' => $this->boolean('is_confirmed'),
        //     'is_invoice' => $this->boolean('is_invoice'),
        // ]);
        Log::debug('[UpdateGigCostRequest] Dados após prepareForValidation: ', $this->all());
    }
}