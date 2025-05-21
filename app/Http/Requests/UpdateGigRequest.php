<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateGigRequest extends GigFormRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // Adicionar regras específicas para update, se necessário
        return $rules;
    }

    public function authorize(): bool
    {
        return true; // Substituir pela Policy quando implementada
    }
}