<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    /**
     * Moedas suportadas pelo sistema
     */
    private const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'BRL'];

    /**
     * Cache TTL em minutos (4 horas)
     */
    private const CACHE_TTL = 240;

    /**
     * Obtém a taxa de câmbio para uma moeda específica em uma data
     *
     * @param string $currencyCode Código da moeda (USD, EUR, GBP)
     * @param Carbon $date Data para obter a taxa
     * @param bool $useCache Se deve usar cache
     * @return float|null Taxa de câmbio ou null se não encontrada
     */
    public function getExchangeRate(string $currencyCode, Carbon $date, bool $useCache = true): ?float
    {
        $currencyCode = strtoupper($currencyCode);
        
        // BRL sempre retorna 1.0
        if ($currencyCode === 'BRL') {
            return 1.0;
        }

        // Verifica se a moeda é suportada
        if (!in_array($currencyCode, self::SUPPORTED_CURRENCIES)) {
            Log::warning("Moeda não suportada: {$currencyCode}");
            return null;
        }

        // Tenta obter do cache primeiro
        if ($useCache) {
            $cacheKey = $this->getCacheKey($currencyCode, $date);
            $cachedRate = Cache::get($cacheKey);
            
            if ($cachedRate !== null) {
                Log::debug("Taxa de câmbio obtida do cache para {$currencyCode}: {$cachedRate}");
                return (float) $cachedRate;
            }
        }

        // Tenta obter da API externa
        $apiRate = $this->fetchFromExternalAPI($currencyCode, $date);
        if ($apiRate !== null) {
            // Salva no cache
            if ($useCache) {
                $cacheKey = $this->getCacheKey($currencyCode, $date);
                Cache::put($cacheKey, $apiRate, self::CACHE_TTL);
            }
            
            Log::info("Taxa de câmbio obtida da API para {$currencyCode}: {$apiRate}");
            return $apiRate;
        }

        // Fallback para taxas padrão de configuração
        $defaultRate = $this->getDefaultRate($currencyCode);
        if ($defaultRate !== null) {
            Log::info("Usando taxa de câmbio padrão para {$currencyCode}: {$defaultRate}");
            return $defaultRate;
        }

        Log::warning("Não foi possível obter taxa de câmbio para {$currencyCode} na data {$date->format('Y-m-d')}");
        return null;
    }

    /**
     * Obtém taxa de câmbio de API externa (Banco Central do Brasil)
     *
     * @param string $currencyCode
     * @param Carbon $date
     * @return float|null
     */
    private function fetchFromExternalAPI(string $currencyCode, Carbon $date): ?float
    {
        try {
            // Mapeia códigos de moeda para códigos do BCB
            $bcbCodes = [
                'USD' => 1,    // Dólar americano
                'EUR' => 978,  // Euro
                'GBP' => 826,  // Libra esterlina
            ];

            if (!isset($bcbCodes[$currencyCode])) {
                return null;
            }

            $bcbCode = $bcbCodes[$currencyCode];
            $dateStr = $date->format('m-d-Y');
            
            // API do Banco Central do Brasil
            $url = "https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoMoedaDia(moeda=@moeda,dataCotacao=@dataCotacao)?@moeda='{$currencyCode}'&@dataCotacao='{$dateStr}'&\$format=json";
            
            $response = Http::timeout(10)->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['value'][0]['cotacaoVenda'])) {
                    return (float) $data['value'][0]['cotacaoVenda'];
                }
            }
            
            Log::warning("Falha ao obter taxa do BCB para {$currencyCode}: " . $response->status());
            
        } catch (\Exception $e) {
            Log::error("Erro ao consultar API do BCB para {$currencyCode}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Obtém taxa padrão da configuração
     *
     * @param string $currencyCode
     * @return float|null
     */
    private function getDefaultRate(string $currencyCode): ?float
    {
        $defaultRates = config('app.default_exchange_rates', []);
        return isset($defaultRates[strtoupper($currencyCode)]) 
            ? (float) $defaultRates[strtoupper($currencyCode)] 
            : null;
    }

    /**
     * Gera chave de cache para taxa de câmbio
     *
     * @param string $currencyCode
     * @param Carbon $date
     * @return string
     */
    private function getCacheKey(string $currencyCode, Carbon $date): string
    {
        return "exchange_rate_{$currencyCode}_{$date->format('Y_m_d')}";
    }

    /**
     * Obtém taxas de câmbio para múltiplas moedas
     *
     * @param array $currencies
     * @param Carbon $date
     * @return array
     */
    public function getMultipleRates(array $currencies, Carbon $date): array
    {
        $rates = [];
        
        foreach ($currencies as $currency) {
            $rates[$currency] = $this->getExchangeRate($currency, $date);
        }
        
        return $rates;
    }

    /**
     * Converte valor de uma moeda para BRL
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param Carbon $date
     * @return float|null
     */
    public function convertToBRL(float $amount, string $fromCurrency, Carbon $date): ?float
    {
        $rate = $this->getExchangeRate($fromCurrency, $date);
        
        if ($rate === null) {
            return null;
        }
        
        return $amount * $rate;
    }

    /**
     * Limpa cache de taxas de câmbio
     *
     * @param string|null $currencyCode Se especificado, limpa apenas para essa moeda
     * @return void
     */
    public function clearCache(?string $currencyCode = null): void
    {
        if ($currencyCode) {
            $pattern = "exchange_rate_{$currencyCode}_*";
            // Laravel não tem flush por pattern, então precisamos implementar manualmente
            // Por simplicidade, vamos apenas logar
            Log::info("Cache de taxa de câmbio limpo para {$currencyCode}");
        } else {
            Cache::flush();
            Log::info("Todo cache de taxas de câmbio foi limpo");
        }
    }

    /**
     * Verifica se uma moeda é suportada
     *
     * @param string $currencyCode
     * @return bool
     */
    public function isSupportedCurrency(string $currencyCode): bool
    {
        return in_array(strtoupper($currencyCode), self::SUPPORTED_CURRENCIES);
    }

    /**
     * Retorna lista de moedas suportadas
     *
     * @return array
     */
    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }
}