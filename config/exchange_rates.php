<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | Lista de moedas suportadas pelo sistema EventosPro.
    | Todas as moedas devem estar em formato ISO 4217 (3 letras maiúsculas).
    |
    */

    'supported_currencies' => [
        'BRL' => [
            'name' => 'Real Brasileiro',
            'symbol' => 'R$',
            'is_base' => true,
        ],
        'USD' => [
            'name' => 'Dólar Americano',
            'symbol' => '$',
            'is_base' => false,
        ],
        'EUR' => [
            'name' => 'Euro',
            'symbol' => '€',
            'is_base' => false,
        ],
        'GBP' => [
            'name' => 'Libra Esterlina',
            'symbol' => '£',
            'is_base' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Exchange Rates
    |--------------------------------------------------------------------------
    |
    | Taxas de câmbio padrão utilizadas quando não há taxa específica
    | disponível de pagamentos confirmados ou APIs externas.
    | Todas as taxas são relativas ao BRL (Real Brasileiro).
    |
    | Estas taxas são utilizadas como fallback pelo ExchangeRateService.
    |
    */

    'default_rates' => [
        'USD' => env('USD_EXCHANGE_RATE', 5.30),
        'EUR' => env('EUR_EXCHANGE_RATE', 5.70),
        'GBP' => env('GBP_EXCHANGE_RATE', 6.50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate API Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para APIs externas de taxa de câmbio.
    |
    */

    'api' => [
        'enabled' => env('EXCHANGE_API_ENABLED', true),
        'timeout' => env('EXCHANGE_API_TIMEOUT', 10), // segundos
        'cache_ttl' => env('EXCHANGE_CACHE_TTL', 240), // minutos (4 horas)

        // URLs das APIs (podem ser configuradas via .env)
        'urls' => [
            'primary' => env('EXCHANGE_API_PRIMARY_URL', 'https://api.exchangerate-api.com/v4/latest/BRL'),
            'fallback' => env('EXCHANGE_API_FALLBACK_URL', 'https://api.fixer.io/latest?base=BRL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Validation Rules
    |--------------------------------------------------------------------------
    |
    | Regras de validação para campos de moeda no sistema.
    |
    */

    'validation' => [
        'currency_field' => 'required|string|size:3|in:'.implode(',', ['BRL', 'USD', 'EUR', 'GBP']),
        'exchange_rate_field' => 'nullable|numeric|min:0.000001|max:999999.999999',
        'amount_field' => 'required|numeric|min:0.01|max:999999999.99',
    ],

];
