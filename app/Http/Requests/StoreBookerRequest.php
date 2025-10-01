<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:bookers,name'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'], // Comissão % opcional
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do booker é obrigatório.',
            'name.unique' => 'Este nome de booker já está cadastrado.',
            'default_commission_rate.max' => 'A taxa de comissão não pode ser maior que 100.',
        ];
    }
}
