<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArtistRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Adicionar Policy depois

    public function rules(): array
    {
        $artistId = $this->route('artist')->id; // Pega o ID da rota

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('artists', 'name')->ignore($artistId)], // Único, ignorando o atual
            'contact_info' => ['nullable', 'string', 'max:65535'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }
     public function messages(): array
     {
          return [
             'name.required' => 'O nome do artista é obrigatório.',
             'name.unique' => 'Este nome de artista já está cadastrado.',
         ];
     }
}