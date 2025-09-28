<?php

namespace Tests\Unit\Services;

use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExchangeRateService $exchangeRateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exchangeRateService = new ExchangeRateService;

        // Configurar taxas padrão para testes
        Config::set('app.default_exchange_rates', [
            'USD' => 5.30,
            'EUR' => 5.70,
            'GBP' => 6.50,
        ]);
    }

    /** @test */
    public function it_returns_1_for_brl_currency()
    {
        $rate = $this->exchangeRateService->getExchangeRate('BRL', Carbon::today());

        $this->assertEquals(1.0, $rate);
    }

    /** @test */
    public function it_returns_null_for_unsupported_currency()
    {
        $rate = $this->exchangeRateService->getExchangeRate('JPY', Carbon::today());

        $this->assertNull($rate);
    }

    /** @test */
    public function it_returns_cached_rate_when_available()
    {
        $date = Carbon::today();
        $cacheKey = "exchange_rate_USD_{$date->format('Y_m_d')}";

        // Simular cache existente
        Cache::put($cacheKey, 5.25, 240);

        $rate = $this->exchangeRateService->getExchangeRate('USD', $date);

        $this->assertEquals(5.25, $rate);
    }

    /** @test */
    public function it_falls_back_to_default_rate_when_api_fails()
    {
        // Simular falha na API
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $rate = $this->exchangeRateService->getExchangeRate('USD', Carbon::today(), false);

        $this->assertEquals(5.30, $rate); // Taxa padrão configurada
    }

    /** @test */
    public function it_fetches_rate_from_api_when_cache_is_disabled()
    {
        // Simular resposta da API do BCB
        Http::fake([
            '*bcb.gov.br*' => Http::response([
                'value' => [
                    ['cotacaoVenda' => 5.45],
                ],
            ], 200),
        ]);

        $rate = $this->exchangeRateService->getExchangeRate('USD', Carbon::today(), false);

        $this->assertEquals(5.45, $rate);
    }

    /** @test */
    public function it_converts_amount_to_brl_correctly()
    {
        // Mock HTTP calls to prevent real API calls
        Http::fake([
            '*' => Http::response([], 404), // Force fallback to default rates
        ]);

        // Configurar taxa conhecida
        Config::set('app.default_exchange_rates.USD', 5.00);

        $convertedAmount = $this->exchangeRateService->convertToBRL(100.00, 'USD', Carbon::today());

        $this->assertEquals(500.00, $convertedAmount);
    }

    /** @test */
    public function it_returns_null_when_conversion_rate_not_available()
    {
        $convertedAmount = $this->exchangeRateService->convertToBRL(100.00, 'JPY', Carbon::today());

        $this->assertNull($convertedAmount);
    }

    /** @test */
    public function it_gets_multiple_rates_correctly()
    {
        // Mock HTTP calls to prevent real API calls
        Http::fake([
            '*' => Http::response([], 404), // Force fallback to default rates
        ]);

        $currencies = ['USD', 'EUR', 'GBP'];
        $rates = $this->exchangeRateService->getMultipleRates($currencies, Carbon::today());

        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('GBP', $rates);
        $this->assertEquals(5.30, $rates['USD']);
        $this->assertEquals(5.70, $rates['EUR']);
        $this->assertEquals(6.50, $rates['GBP']);
    }

    /** @test */
    public function it_checks_supported_currencies_correctly()
    {
        $this->assertTrue($this->exchangeRateService->isSupportedCurrency('USD'));
        $this->assertTrue($this->exchangeRateService->isSupportedCurrency('eur')); // Case insensitive
        $this->assertTrue($this->exchangeRateService->isSupportedCurrency('BRL'));
        $this->assertFalse($this->exchangeRateService->isSupportedCurrency('JPY'));
    }

    /** @test */
    public function it_returns_supported_currencies_list()
    {
        $currencies = $this->exchangeRateService->getSupportedCurrencies();

        $this->assertIsArray($currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
        $this->assertContains('GBP', $currencies);
        $this->assertContains('BRL', $currencies);
    }

    /** @test */
    public function it_handles_api_timeout_gracefully()
    {
        // Simular timeout da API
        Http::fake([
            '*' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $rate = $this->exchangeRateService->getExchangeRate('USD', Carbon::today(), false);

        // Deve retornar a taxa padrão quando a API falha
        $this->assertEquals(5.30, $rate);
    }

    /** @test */
    public function it_caches_api_response_correctly()
    {
        $date = Carbon::today();
        $cacheKey = "exchange_rate_USD_{$date->format('Y_m_d')}";

        // Simular resposta da API
        Http::fake([
            '*bcb.gov.br*' => Http::response([
                'value' => [
                    ['cotacaoVenda' => 5.35],
                ],
            ], 200),
        ]);

        // Primeira chamada deve buscar da API e cachear
        $rate1 = $this->exchangeRateService->getExchangeRate('USD', $date, true);

        // Verificar se foi cacheado
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals(5.35, Cache::get($cacheKey));

        // Segunda chamada deve usar o cache
        Http::fake([]); // Limpar fake para garantir que não chama API novamente
        $rate2 = $this->exchangeRateService->getExchangeRate('USD', $date, true);

        $this->assertEquals($rate1, $rate2);
        $this->assertEquals(5.35, $rate2);
    }

    /** @test */
    public function it_handles_malformed_api_response()
    {
        // Simular resposta malformada da API
        Http::fake([
            '*bcb.gov.br*' => Http::response([
                'error' => 'Invalid request',
            ], 200),
        ]);

        $rate = $this->exchangeRateService->getExchangeRate('USD', Carbon::today(), false);

        // Deve retornar a taxa padrão quando a resposta é malformada
        $this->assertEquals(5.30, $rate);
    }

    /** @test */
    public function it_handles_case_insensitive_currency_codes()
    {
        // Mock HTTP calls to prevent real API calls
        Http::fake([
            '*' => Http::response([], 404), // Force fallback to default rates
        ]);

        $rateUpper = $this->exchangeRateService->getExchangeRate('USD', Carbon::today());
        $rateLower = $this->exchangeRateService->getExchangeRate('usd', Carbon::today());
        $rateMixed = $this->exchangeRateService->getExchangeRate('Usd', Carbon::today());

        $this->assertEquals($rateUpper, $rateLower);
        $this->assertEquals($rateUpper, $rateMixed);
        $this->assertEquals(5.30, $rateUpper);
    }
}
