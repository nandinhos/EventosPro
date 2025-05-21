<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class GigFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Informações Contratuais
            'contract_status'     => ['nullable', Rule::in(['assinado', 'para_assinatura', 'n/a'])],
            'contract_number'     => ['nullable', 'string', 'max:255'],
            'contract_date'       => ['nullable', 'date'],

            // Detalhes do Evento
            'artist_id'           => ['required', 'exists:artists,id'],
            'booker_id'           => ['nullable', 'exists:bookers,id'],
            'gig_date'            => ['required', 'date'],
            'location_event_details' => ['required', 'string', 'max:255'],
            'cache_value'         => ['required', 'numeric', 'min:0.01'],
            'currency'            => ['required', Rule::in(['BRL', 'USD', 'EUR', 'GBP'])],
            'exchange_rate'       => ['nullable', Rule::requiredIf(fn () => strtoupper($this->input('currency')) !== 'BRL'), 'numeric', 'min:0.0001'],

            // Comissões
            'agency_commission_type'   => ['nullable', Rule::in(['fixa', 'porcentagem'])],
            'agency_commission_value'  => ['nullable', 'numeric', 'min:0'],
            'booker_commission_type'   => ['nullable', Rule::in(['fixa', 'porcentagem'])],
            'booker_commission_value'  => ['nullable', 'numeric', 'min:0'],

            // Despesas (relacionadas ao GigCost)
            'cost_center_id.*'    => ['nullable', 'exists:cost_centers,id'],
            'description.*'       => ['nullable', 'string'],
            'value.*'             => ['nullable', 'numeric', 'min:0.01'],

            // Tags & Notas
            'tags'                => ['nullable', 'array', 'exists:tags,id'],
            'notes'               => ['nullable', 'string'],
        ];
    }

    public function prepareForValidation()
    {
        $data = $this->only([
            'contract_status', 'contract_number', 'contract_date',
            'artist_id', 'booker_id', 'gig_date', 'location_event_details',
            'cache_value', 'currency', 'exchange_rate',
            'agency_commission_type', 'agency_commission_value',
            'booker_commission_type', 'booker_commission_value',
            'tags', 'notes'
        ]);

        // Valores default para campos vazios
        $data['contract_status'] = $this->input('contract_status') ?: 'n/a';
        $data['currency'] = strtoupper($this->input('currency')) ?: 'BRL';

        $this->merge($data);
    }
}