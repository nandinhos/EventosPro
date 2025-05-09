<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGigCostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cost_center_id' => ['sometimes', 'required', 'integer', 'exists:cost_centers,id'],
            'description'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'value'          => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'currency'       => ['sometimes', 'required', 'string', 'max:10', Rule::in(['BRL', 'USD', 'EUR', 'GBP'])],
            'expense_date'   => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'payer_type'     => ['sometimes', 'required', 'string', Rule::in(['agencia', 'artista', 'cliente', 'outro'])],
            'payer_details'  => ['sometimes', 'nullable', 'string', 'max:255', Rule::requiredIf($this->payer_type === 'outro')],
            'notes'          => ['sometimes', 'nullable', 'string', 'max:65535'],
        ];
    }
     public function messages(): array
    {
        return [ /* ... (mesmas mensagens do Store) ... */ ];
    }
    // prepareForValidation pode ser igual ao Store se necessário
}