<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Policy - verificar se user pode confirmar pagamento desta gig/payment
        return true;
    }

    public function rules(): array
    {
        // Valida os campos que podem ser ajustados na confirmação
        return [
            'received_date_actual' => ['required', 'date'],
            'received_value_actual' => ['required', 'numeric', 'min:0'], // Permitir 0?
            'currency' => ['required', 'string', 'max:10'], // Moeda REAL recebida
            'exchange_rate' => [
                'nullable',
                Rule::requiredIf(fn() => strtoupper($this->input('currency', 'BRL')) !== 'BRL'),
                'numeric',
                'min:0'
            ],
             // 'confirmation_notes' => ['nullable', 'string'] // Se quiser um campo de nota específico para confirmação
        ];
    }

     public function messages(): array
     {
         return [
            'received_date_actual.required' => 'Informe a data real do recebimento.',
            'received_value_actual.required' => 'Informe o valor real recebido.',
            // ... outras mensagens ...
         ];
     }

      // Prepare for validation (ex: default date, handle currency/rate)
      protected function prepareForValidation(): void
      {
         if (!$this->filled('received_date_actual')) {
             $this->merge(['received_date_actual' => today()->format('Y-m-d')]);
         }
         if (strtoupper($this->input('currency', 'BRL')) === 'BRL') {
            $this->merge(['exchange_rate' => null]);
        }
     }
}