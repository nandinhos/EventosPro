<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_booker' => ['boolean'],

            // Se o usuário já tem um booker, excluímos esses campos da validação usando a regra "exclude"
            'booker_creation_type' => $user->booker()->exists()
                ? ['exclude']
                : ['required_if:is_booker,true', 'string', 'in:existing,new'],

            'existing_booker_id' => $user->booker()->exists()
                ? ['exclude']
                : ['required_if:booker_creation_type,existing', 'integer', 'exists:bookers,id'],

            'booker_name' => $user->booker()->exists()
                ? ['exclude']
                : ['required_if:booker_creation_type,new', 'string', 'max:255', Rule::unique('bookers', 'name')],

            'default_commission_rate' => $user->booker()->exists()
                ? ['exclude']
                : ['required_if:booker_creation_type,new', 'nullable', 'numeric', 'min:0', 'max:100'],

            'contact_info' => ['nullable', 'string', 'max:255'],
        ];
    }
}
