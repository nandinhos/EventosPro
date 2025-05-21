<?php

namespace App\Http\Requests;

class StoreGigRequest extends GigFormRequest
{
    public function authorize(): bool
    {
        return true; // Substituir pela Policy quando implementada
    }
}