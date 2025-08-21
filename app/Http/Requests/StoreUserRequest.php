<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Geralmente, a autorização é feita aqui. Para este exemplo, permitimos.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Dados básicos do usuário
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],

            // Campos do booker
            // 'is_booker' deve ser um booleano. O valor '1' do checkbox é convertido automaticamente pelo Laravel.
            'is_booker' => ['boolean'],
            // 'booker_creation_type' é obrigatório se 'is_booker' for verdadeiro e deve ser 'existing' ou 'new'.
            'booker_creation_type' => ['required_if:is_booker,true', 'in:existing,new'],

            // Campos para booker existente
            // 'existing_booker_id' é obrigatório se 'booker_creation_type' for 'existing'.
            // É proibido se 'booker_creation_type' for 'new'.
            // Deve existir na tabela 'bookers'.
            'existing_booker_id' => [
                'required_if:booker_creation_type,existing',
                'prohibited_if:booker_creation_type,new',
                'exists:bookers,id',
            ],

            // Campos para novo booker
            // 'booker_name' é obrigatório se 'booker_creation_type' for 'new'.
            // É proibido se 'booker_creation_type' for 'existing'.
            'booker_name' => [
                'required_if:booker_creation_type,new',
                'prohibited_if:booker_creation_type,existing',
                'string',
                'max:255',
            ],
            // 'default_commission_rate' é obrigatório se 'booker_creation_type' for 'new'.
            // É proibido se 'booker_creation_type' for 'existing'.
            'default_commission_rate' => [
                'required_if:booker_creation_type,new',
                'prohibited_if:booker_creation_type,existing',
                'numeric',
                'between:0,100',
            ],
            // 'contact_info' é opcional.
            'contact_info' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * Define nomes mais amigáveis para os campos nos erros de validação.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'booker_creation_type' => 'tipo de criação do booker',
            'existing_booker_id' => 'booker existente',
            'booker_name' => 'nome do booker',
            'default_commission_rate' => 'taxa de comissão padrão',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * Personaliza as mensagens de erro para cada regra.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_booker.boolean' => 'O campo "Este usuário é um Booker?" deve ser verdadeiro ou falso.',
            'booker_creation_type.required_if' => 'O tipo de criação do booker é obrigatório quando o usuário é um booker.',
            'booker_creation_type.in' => 'O tipo de criação do booker selecionado é inválido.',
            'existing_booker_id.required_if' => 'Selecione um booker existente.',
            'existing_booker_id.prohibited_if' => 'O campo "booker existente" não deve ser preenchido ao cadastrar um novo booker.',
            'existing_booker_id.exists' => 'O booker selecionado não existe.',
            'booker_name.required_if' => 'O nome do booker é obrigatório para um novo booker.',
            'booker_name.prohibited_if' => 'O campo "nome do booker" não deve ser preenchido ao associar um booker existente.',
            'default_commission_rate.required_if' => 'A taxa de comissão é obrigatória para um novo booker.',
            'default_commission_rate.prohibited_if' => 'O campo "taxa de comissão padrão" não deve ser preenchido ao associar um booker existente.',
            'default_commission_rate.between' => 'A taxa de comissão deve estar entre 0 e 100.',
        ];
    }
}
