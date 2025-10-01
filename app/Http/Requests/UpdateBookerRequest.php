<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookerId = $this->route('booker')->id; // Pega o ID da rota

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('bookers', 'name')->ignore($bookerId)],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
