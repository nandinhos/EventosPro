<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule; // Adicionar Log

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Log::debug("Autorizando UpdatePaymentRequest..."); // Log para ver se authorize é chamado
        // TODO: Policy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Não precisamos do ID da Gig ou Pagamento aqui,
        // pois a validação é sobre os DADOS ENVIADOS.
        // A verificação de pertencimento é feita no controller ou Policy.
        // Log::debug("Executando regras de validação para UpdatePaymentRequest..."); // Log

        return [
            'due_value' => ['required', 'numeric', 'min:0.01'], // Renomeado de received_value
            'due_date' => ['required', 'date'],                // Renomeado de received_date
            'currency' => ['required', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GBP'])],
            'exchange_rate' => [
                'nullable',
                Rule::requiredIf(fn () => strtoupper($this->input('currency', 'BRL')) !== 'BRL'),
                'numeric',
                'min:0',
            ],
            'description' => ['nullable', 'string', 'max:255'], // Adicionado description se tiver no form
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        // Log::debug("Obtendo mensagens de erro personalizadas..."); // Log
        return [
            'due_value.required' => 'O Valor Devido é obrigatório.',
            'due_value.min' => 'O Valor Devido deve ser positivo.',
            'due_date.required' => 'A Data de Vencimento é obrigatória.',
            'currency.required' => 'A Moeda é obrigatória.',
            'exchange_rate.required_if' => 'A Taxa de Câmbio é obrigatória quando a moeda não é BRL.',
            // ... outras mensagens ...
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Log::debug("Preparando dados para validação (Update):", $this->all()); // Log
        $toMerge = [];
        // Não precisamos mais limpar expenses_value_brl aqui, pois não vem do form de pagamento
        // Não precisamos limpar booker_id aqui

        if (strtoupper($this->input('currency', 'BRL')) === 'BRL') {
            $toMerge['exchange_rate'] = null;
        }
        if (! empty($toMerge)) {
            $this->merge($toMerge);
        }
        // Log::debug("Dados preparados (Update):", $this->all()); // Log
    }

    /**
     * Handle a failed validation attempt.
     * (Para logar erros de validação explicitamente)
     *
     * @param Validator $validator
     * @return void
     *
     * @throws ValidationException
     */
    // protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    // {
    //     Log::error('Falha na validação (UpdatePaymentRequest): ', $validator->errors()->toArray());
    //     parent::failedValidation($validator);
    // }

}
