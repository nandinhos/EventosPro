<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CostCenterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage cost-centers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $costCenterId = $this->route('cost_center');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cost_centers', 'name')->ignore($costCenterId),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
            'is_active' => 'status ativo',
            'color' => 'cor',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O campo :attribute é obrigatório.',
            'name.unique' => 'Já existe um centro de custo com este nome.',
            'color.regex' => 'A :attribute deve estar no formato hexadecimal (#RRGGBB).',
        ];
    }
}
