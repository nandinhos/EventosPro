<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Services\GigFinancialCalculatorService; // Importar o novo Service
use Illuminate\Support\Facades\App; // Para resolver o Service
use Illuminate\Support\Facades\Log; // Para logs
use Illuminate\Database\Eloquent\Casts\Attribute; // Importar para nova sintaxe de Accessor


class Gig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'artist_id',
        'booker_id',
        'contract_number',
        'contract_date',
        'gig_date',
        'location_event_details',
        'cache_value', // Valor original do contrato/cachê
        'currency',    // Moeda original do contrato/cachê
        // 'exchange_rate', // Removido da tabela 'gigs'
        // 'cache_value_brl', // Removido da tabela 'gigs', será um accessor
        // 'expenses_value_brl', // Removido da tabela 'gigs'

        'agency_commission_type', // 'percent' ou 'fixed'
        'agency_commission_rate', // Taxa percentual (ex: 20 para 20%)
        'agency_commission_value',// Valor fixo em BRL OU valor calculado em BRL (se tipo 'percent') - Armazenado no DB

        'booker_commission_type', // 'percent' ou 'fixed'
        'booker_commission_rate', // Taxa percentual
        'booker_commission_value',// Valor fixo em BRL OU valor calculado em BRL (se tipo 'percent') - Armazenado no DB

        'liquid_commission_value',// Comissão líquida da agência (Agência Bruta - Booker) em BRL - Armazenado no DB

        'payment_status',         // Status do pagamento PELO CLIENTE (a_vencer, vencido, pago)
        'artist_payment_status',  // Status do pagamento AO ARTISTA (pendente, pago)
        'booker_payment_status',  // Status do pagamento AO BOOKER (pendente, pago)
        'contract_status',
        'notes',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'gig_date' => 'date',
        'cache_value' => 'decimal:2', // Valor original na moeda da Gig
        // Não há cast para exchange_rate ou cache_value_brl pois não são colunas diretas
        'agency_commission_rate' => 'decimal:2',
        'agency_commission_value' => 'decimal:2', // Valor em BRL
        'booker_commission_rate' => 'decimal:2',
        'booker_commission_value' => 'decimal:2', // Valor em BRL
        'liquid_commission_value' => 'decimal:2', // Valor em BRL
    ];

    // --- Relacionamentos ---
    public function artist(): BelongsTo { return $this->belongsTo(Artist::class); }
    public function booker(): BelongsTo { return $this->belongsTo(Booker::class)->withDefault(); } // withDefault para evitar erro se booker_id for null
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function settlement(): HasOne { return $this->hasOne(Settlement::class); }
    public function tags(): MorphToMany { return $this->morphToMany(Tag::class, 'taggable'); }
    public function costs(): HasMany { return $this->hasMany(GigCost::class); }

    // --- Instância do Service ---
    // Para evitar múltiplas instanciações do service para o mesmo objeto Gig
    protected ?GigFinancialCalculatorService $financialCalculator = null;

    protected function getFinancialCalculator(): GigFinancialCalculatorService
    {
        if ($this->financialCalculator === null) {
            $this->financialCalculator = App::make(GigFinancialCalculatorService::class);
        }
        return $this->financialCalculator;
    }

    // --- Accessors (Atributos Calculados Dinamicamente) ---

    

    /**
     * Função auxiliar para buscar taxa de câmbio.
     * SUBSTITUIR POR LÓGICA REAL OU CONFIGURÁVEL.
     * Por ora, usamos valores fixos para ilustração e a regra de pegar câmbio do pagamento.
     * Para projeções, podemos ter um default.
     *
     * @param string $currencyCode
     * @param Carbon $date
     * @return float|null
     */
    public function getExchangeRateForCurrency(string $currencyCode, Carbon $date): ?float
    {
        // Lógica de Projeção: Tentar pegar a taxa de câmbio do primeiro pagamento confirmado, se houver.
        // Se não, usar uma taxa de placeholder/configurável.
        $firstConfirmedPaymentWithRate = $this->payments()
            ->whereNotNull('confirmed_at')
            ->where('currency', $currencyCode) // Considerar se a parcela confirmada está na mesma moeda da Gig
            ->whereNotNull('exchange_rate') // Usar a coluna exchange_rate existente
            ->orderBy('received_date_actual', 'asc')
            ->first();

        if ($firstConfirmedPaymentWithRate && $firstConfirmedPaymentWithRate->exchange_rate > 0) {
            // Usar a taxa de câmbio do pagamento confirmado
            Log::info("[Gig ID {$this->id}] Usando taxa de câmbio do pagamento confirmado {$firstConfirmedPaymentWithRate->id} para {$currencyCode}: {$firstConfirmedPaymentWithRate->exchange_rate}");
            return (float) $firstConfirmedPaymentWithRate->exchange_rate;
        }
        
        // Placeholder/Configurável para projeção se não houver pagamento confirmado com taxa
        $defaultRates = [
            'USD' => (float) (config('app.default_exchange_rates.usd') ?? 5.20),
            'EUR' => (float) (config('app.default_exchange_rates.eur') ?? 5.60),
            'GBP' => (float) (config('app.default_exchange_rates.gbp') ?? 6.20),
            'GPB' => (float) (config('app.default_exchange_rates.gbp') ?? 6.20), // Alias para GBP (correção de erro de digitação)
        ];
        Log::info("[Gig ID {$this->id}] Usando taxa de câmbio padrão para projeção para {$currencyCode}: " . ($defaultRates[strtoupper($currencyCode)] ?? null));
        return $defaultRates[strtoupper($currencyCode)] ?? null; // Retorna a taxa ou null
    }

    /**
     * Retorna o "Cachê Bruto" da Gig em BRL (Base de Comissão).
     * Fórmula: Valor do Contrato em BRL - Despesas Pagas pela Agência.
     * Utiliza o GigFinancialCalculatorService.
     *
     * @return float
     */
    public function getGrossCashBrlAttribute(): float // Novo nome sugerido, ou manter getCommissionBaseBrlAttribute
    {
        return $this->getFinancialCalculator()->calculateGrossCashBrl($this);
    }

    /**
     * Retorna o valor total das despesas confirmadas em BRL.
     * Utiliza o GigFinancialCalculatorService.
     *
     * @return float
     */
    public function getTotalConfirmedExpensesBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateTotalConfirmedExpensesBrl($this);
    }

    /**
     * Retorna o valor total das despesas confirmadas e marcadas como reembolsáveis (NF Artista) em BRL.
     * Utiliza o GigFinancialCalculatorService.
     *
     * @return float
     */
    public function getTotalReimbursableExpensesBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateTotalReimbursableExpensesBrl($this);
    }

    /**
     * Accessor para obter o valor da "Comissão Bruta da Agência" em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     * Este accessor APENAS LÊ. O valor é persistido no banco pelo GigObserver.
     *
     * @return float
     */
    public function getCalculatedAgencyGrossCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateAgencyGrossCommissionBrl($this);
    }

    /**
     * Accessor para obter o valor da "Comissão do Booker" em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     * Este accessor APENAS LÊ. O valor é persistido no banco pelo GigObserver.
     *
     * @return float
     */
    public function getCalculatedBookerCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateBookerCommissionBrl($this);
    }

    /**
     * Accessor para obter o valor da "Comissão Líquida da Agência" em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     * Este accessor APENAS LÊ. O valor é persistido no banco pelo GigObserver.
     *
     * @return float
     */
    public function getCalculatedAgencyNetCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateAgencyNetCommissionBrl($this);
    }

    /**
     * Accessor para obter o "Cachê Líquido do Artista" em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     *
     * @return float
     */
    public function getCalculatedArtistNetPayoutBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateArtistNetPayoutBrl($this);
    }

    /**
     * Accessor para obter o valor final da Nota Fiscal do Artista em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     *
     * @return float
     */
    public function getCalculatedArtistInvoiceValueBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateArtistInvoiceValueBrl($this);
    }

    /**
     * Retorna a taxa de câmbio a ser usada para a Gig.
     * Prioriza a taxa do primeiro pagamento confirmado.
     * Se não houver, usa uma taxa de projeção configurada.
     *
     * @return array contendo 'rate' e 'type' ('confirmed' ou 'projected')
     */
    public function getExchangeRateDetails(): array
    {
        if (strtoupper($this->currency ?? 'BRL') === 'BRL') {
            return ['rate' => 1.0, 'type' => 'confirmed'];
        }

        // Tenta encontrar a taxa de um pagamento já confirmado
        $firstConfirmedPayment = $this->payments()
            ->whereNotNull('confirmed_at')
            ->whereNotNull('exchange_rate')
            ->orderBy('received_date_actual', 'asc')
            ->first();

        if ($firstConfirmedPayment && $firstConfirmedPayment->exchange_rate > 0) {
            return [
                'rate' => (float) $firstConfirmedPayment->exchange_rate,
                'type' => 'confirmed', // A taxa é de um pagamento real
            ];
        }

        // Se não encontrou, usa uma taxa de projeção do arquivo de configuração
        $defaultRates = config('app.default_exchange_rates', []);
        $rate = $defaultRates[strtoupper($this->currency)] ?? null;

        return [
            'rate' => $rate,
            'type' => 'projected', // A taxa é uma estimativa
        ];
    }

    /**
     * Calcula o valor total efetivamente recebido em BRL.
     * Este método é a "fonte da verdade" para o valor real em BRL,
     * pois soma cada pagamento confirmado usando sua própria taxa de câmbio.
     *
     * @return float
     */
    public function getTotalReceivedBrlAttribute(): float
    {
        $this->loadMissing('payments');

        return (float) $this->payments
            ->whereNotNull('confirmed_at')
            ->sum(function ($payment) {
                // Se o pagamento confirmado foi em BRL, usa o valor recebido.
                if (strtoupper($payment->currency) === 'BRL') {
                    return $payment->received_value_actual;
                }
                // Se foi em outra moeda, converte usando a taxa de câmbio registrada NAQUELE pagamento.
                if ($payment->exchange_rate) {
                    return $payment->received_value_actual * $payment->exchange_rate;
                }
                // Retorna 0 se um pagamento confirmado em moeda estrangeira não tiver taxa (cenário de erro de dados).
                return 0;
            });
    }

    /**
     * Accessor INTELIGENTE para o Valor do Contrato em BRL.
     * Retorna um array com o valor, o tipo ('confirmed' ou 'projected') e a taxa usada.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function cacheValueBrlDetails(): Attribute
    {
        return Attribute::make(
            get: function () {
                $originalValue = (float) $this->cache_value;
                $gigCurrency = strtoupper($this->currency ?? 'BRL');

                // Se a moeda já é BRL, o valor é sempre "confirmado" e a taxa é 1.
                if ($gigCurrency === 'BRL') {
                    return [
                        'value' => $originalValue,
                        'type' => 'confirmed',
                        'rate_used' => 1.0,
                    ];
                }

                // Verifica se a Gig está totalmente paga
                $isFullyPaid = $this->payment_status === 'pago';

                if ($isFullyPaid) {
                    // SE TOTALMENTE PAGO, o "Valor Contrato BRL" é a soma real de todos os pagamentos convertidos.
                    $confirmedBrlValue = $this->total_received_brl; // Usa o accessor que acabamos de criar
                    $effectiveRate = ($originalValue > 0) ? $confirmedBrlValue / $originalValue : null;

                    Log::debug("[Accessor] Gig #{$this->id} está PAGA. Valor BRL confirmado: {$confirmedBrlValue}");
                    
                    return [
                        'value' => $confirmedBrlValue,
                        'type' => 'confirmed',
                        'rate_used' => $effectiveRate, // Taxa de câmbio média efetiva
                    ];
                } else {
                    // SE AINDA NÃO ESTÁ PAGO, usamos uma taxa de PROJEÇÃO.
                    $defaultRates = config('app.default_exchange_rates', []);
                    $projectionRate = $defaultRates[$gigCurrency] ?? null;
                    
                    if ($projectionRate) {
                        $projectedValue = $originalValue * $projectionRate;
                        Log::debug("[Accessor] Gig #{$this->id} está PENDENTE. Valor BRL projetado: {$projectedValue}");
                        return [
                            'value' => $projectedValue,
                            'type' => 'projected',
                            'rate_used' => $projectionRate,
                        ];
                    }
                }

                // Fallback: Se não está pago e não há taxa de projeção, não podemos calcular.
                Log::warning("[Accessor] Não foi possível calcular valor BRL para Gig #{$this->id}.");
                return [
                    'value' => null,
                    'type' => 'unavailable',
                    'rate_used' => null,
                ];
            },
        );
    }

    // Accessor antigo, agora DEPRECADO em favor de cacheValueBrlDetails['value'].
    // Mantemos por retrocompatibilidade se outras partes ainda o usarem, mas ele usará a nova lógica.
    public function getCacheValueBrlAttribute(): ?float
    {
        return $this->cacheValueBrlDetails['value'];
    }

    /**
     * Accessor para verificar se todas as despesas da gig foram confirmadas.
     *
     * @return bool
     */
    public function getAreAllCostsConfirmedAttribute(): bool
    {
        // Se não houver custos, consideramos que "todos" estão confirmados.
        if ($this->costs->isEmpty()) {
            return true;
        }
        // Retorna true apenas se NÃO EXISTIR nenhum custo com is_confirmed = false.
        return $this->costs()->where('is_confirmed', false)->doesntExist();
    }
    


    // Manteremos os campos `agency_commission_value`, `booker_commission_value`,
    // e `liquid_commission_value` como colunas no banco que serão preenchidas
    // pelo GigObserver (que usará o Service).
    // Os accessors acima com `Calculated` no nome servem para obter o valor
    // "em tempo real" via service, útil para verificação ou se não quisermos
    // depender 100% do valor armazenado. Para exibição geral, usaremos os campos
    // da tabela que o Observer preencheu.

}