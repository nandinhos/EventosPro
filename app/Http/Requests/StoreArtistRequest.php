<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtistRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Permitir usuários logados

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:artists,name'], // Nome é obrigatório e único
            'contact_info' => ['nullable', 'string', 'max:65535'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }
     public function messages(): array // Mensagens personalizadas
     {
         return [
             'name.required' => 'O nome do artista é obrigatório.',
             'name.unique' => 'Este nome de artista já está cadastrado.',
         ];
     }
}