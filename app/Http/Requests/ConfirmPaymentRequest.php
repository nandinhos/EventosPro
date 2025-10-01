<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_date_actual' => ['required', 'date', 'before_or_equal:today'],
            'received_value_actual' => ['required', 'numeric', 'min:0.01'],
            'currency_received_actual' => ['required', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GBP'])], // Nome do campo do form
            'exchange_rate_received_actual' => [ // Nome do campo do form
                'nullable',
                Rule::requiredIf(fn () => strtoupper($this->input('currency_received_actual', 'BRL')) !== 'BRL'),
                'numeric', 'min:0',
            ],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'received_date_actual.required' => 'A data do recebimento é obrigatória.',
            'received_date_actual.before_or_equal' => 'A data do recebimento não pode ser futura.',
            'received_value_actual.required' => 'O valor recebido é obrigatório.',
            'currency_received_actual.required' => 'A moeda recebida é obrigatória.',
            'exchange_rate_received_actual.required_if' => 'O câmbio é obrigatório se a moeda recebida não for BRL.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Garante que exchange_rate seja null se moeda for BRL
        if (strtoupper($this->input('currency_received_actual', 'BRL')) === 'BRL') {
            $this->merge(['exchange_rate_received_actual' => null]);
        }
    }
}
