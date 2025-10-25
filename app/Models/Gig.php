<?php

namespace App\Models;

use App\Services\ExchangeRateService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany; // Importar o novo Service
use Illuminate\Database\Eloquent\SoftDeletes; // Para resolver o Service
use Illuminate\Support\Facades\App; // Para logs
use Illuminate\Support\Facades\Log; // Importar para nova sintaxe de Accessor

/**
 * @property int $id
 * @property int $artist_id
 * @property int|null $booker_id
 * @property string|null $contract_number
 * @property \Carbon\Carbon|null $contract_date
 * @property \Carbon\Carbon $gig_date
 * @property string $location_event_details
 * @property float $cache_value
 * @property string $currency
 * @property string|null $agency_commission_type
 * @property float|null $agency_commission_rate
 * @property float|null $agency_commission_value
 * @property string|null $booker_commission_type
 * @property float|null $booker_commission_rate
 * @property float|null $booker_commission_value
 * @property float|null $liquid_commission_value
 * @property string $contract_status
 * @property string $payment_status
 * @property string $artist_payment_status
 * @property string $booker_payment_status
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\Artist $artist
 * @property-read \App\Models\Booker $booker
 * @property-read Collection<int, \App\Models\Payment> $payments
 * @property-read \App\Models\Settlement|null $settlement
 * @property-read Collection<int, \App\Models\Tag> $tags
 * @property-read Collection<int, \App\Models\GigCost> $gigCosts
 */
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
        'agency_commission_value', // Valor fixo em BRL OU valor calculado em BRL (se tipo 'percent') - Armazenado no DB

        'booker_commission_type', // 'percent' ou 'fixed'
        'booker_commission_rate', // Taxa percentual
        'booker_commission_value', // Valor fixo em BRL OU valor calculado em BRL (se tipo 'percent') - Armazenado no DB

        'liquid_commission_value', // Comissão líquida da agência (Agência Bruta - Booker) em BRL - Armazenado no DB

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
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function booker(): BelongsTo
    {
        return $this->belongsTo(Booker::class)->withDefault();
    } // withDefault para evitar erro se booker_id for null

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(Settlement::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function gigCosts(): HasMany
    {
        return $this->hasMany(GigCost::class);
    }

    public function costs(): HasMany
    {
        return $this->gigCosts();
    }

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
     * Função para buscar taxa de câmbio usando ExchangeRateService.
     * Prioriza taxas de pagamentos confirmados, depois usa o service para obter taxas atualizadas.
     */
    public function getExchangeRateForCurrency(string $currencyCode, Carbon $date): ?float
    {
        $currencyCode = strtoupper($currencyCode);

        // BRL sempre retorna 1.0
        if ($currencyCode === 'BRL') {
            return 1.0;
        }

        // Prioridade 1: Tentar pegar a taxa de câmbio do primeiro pagamento confirmado na mesma moeda
        $firstConfirmedPaymentWithRate = $this->payments()
            ->whereNotNull('confirmed_at')
            ->where('currency', $currencyCode)
            ->whereNotNull('exchange_rate')
            ->where('exchange_rate', '>', 0)
            ->orderBy('received_date_actual', 'asc')
            ->first();

        if ($firstConfirmedPaymentWithRate) {
            // Log::info("[Gig ID {$this->id}] Usando taxa de câmbio do pagamento confirmado {$firstConfirmedPaymentWithRate->id} para {$currencyCode}: {$firstConfirmedPaymentWithRate->exchange_rate}");

            return (float) $firstConfirmedPaymentWithRate->exchange_rate;
        }

        // Prioridade 2: Usar ExchangeRateService para obter taxa atualizada
        $exchangeRateService = app(ExchangeRateService::class);
        $rate = $exchangeRateService->getExchangeRate($currencyCode, $date);

        if ($rate !== null) {
            // Log::info("[Gig ID {$this->id}] Usando taxa de câmbio do ExchangeRateService para {$currencyCode}: {$rate}");

            return $rate;
        }

        // Log::warning("[Gig ID {$this->id}] Não foi possível obter taxa de câmbio para {$currencyCode} na data {$date->format('Y-m-d')}");

        return null;
    }

    /**
     * Retorna o "Cachê Bruto" da Gig em BRL (Base de Comissão).
     * Fórmula: Valor do Contrato em BRL - Despesas Pagas pela Agência.
     * Utiliza o GigFinancialCalculatorService.
     */
    public function getGrossCashBrlAttribute(): float // Novo nome sugerido, ou manter getCommissionBaseBrlAttribute
    {
        return $this->getFinancialCalculator()->calculateGrossCashBrl($this);
    }

    /**
     * Retorna o valor total das despesas confirmadas em BRL.
     * Utiliza o GigFinancialCalculatorService.
     */
    public function getTotalConfirmedExpensesBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateTotalConfirmedExpensesBrl($this);
    }

    /**
     * Retorna o valor total das despesas confirmadas e marcadas como reembolsáveis (NF Artista) em BRL.
     * Utiliza o GigFinancialCalculatorService.
     */
    public function getTotalReimbursableExpensesBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateTotalReimbursableExpensesBrl($this);
    }

    /**
     * Accessor para obter o valor da "Comissão Bruta da Agência" em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     * Este accessor APENAS LÊ. O valor é persistido no banco pelo GigObserver.
     */
    public function getCalculatedAgencyGrossCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateAgencyGrossCommissionBrl($this);
    }

    /**
     * Accessor para obter o valor da "Comissão do Booker" em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     * Este accessor APENAS LÊ. O valor é persistido no banco pelo GigObserver.
     */
    public function getCalculatedBookerCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateBookerCommissionBrl($this);
    }

    /**
     * Accessor para obter o valor da "Comissão Líquida da Agência" em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     * Este accessor APENAS LÊ. O valor é persistido no banco pelo GigObserver.
     */
    public function getCalculatedAgencyNetCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateAgencyNetCommissionBrl($this);
    }

    /**
     * Accessor para obter o "Cachê Líquido do Artista" em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
     */
    public function getCalculatedArtistNetPayoutBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateArtistNetPayoutBrl($this);
    }

    /**
     * Accessor para obter o valor final da Nota Fiscal do Artista em BRL.
     * Delega o cálculo para o GigFinancialCalculatorService.
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
        $defaultRates = config('exchange_rates.default_rates', []);
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

                    // Log::debug("[Accessor] Gig #{$this->id} está PAGA. Valor BRL confirmado: {$confirmedBrlValue}");

                    return [
                        'value' => $confirmedBrlValue,
                        'type' => 'confirmed',
                        'rate_used' => $effectiveRate, // Taxa de câmbio média efetiva
                    ];
                } else {
                    // SE AINDA NÃO ESTÁ PAGO, usamos uma taxa de PROJEÇÃO.
                    $defaultRates = config('exchange_rates.default_rates', []);
                    $projectionRate = $defaultRates[$gigCurrency] ?? null;

                    if ($projectionRate) {
                        $projectedValue = $originalValue * $projectionRate;
                        // Log::debug("[Accessor] Gig #{$this->id} está PENDENTE. Valor BRL projetado: {$projectedValue}");

                        return [
                            'value' => $projectedValue,
                            'type' => 'projected',
                            'rate_used' => $projectionRate,
                        ];
                    }
                }

                // Fallback: Se não está pago e não há taxa de projeção, não podemos calcular.
                // Log::warning("[Accessor] Não foi possível calcular valor BRL para Gig #{$this->id}.");

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
     */
    public function getAreAllCostsConfirmedAttribute(): bool
    {
        // Se não houver custos, consideramos que "todos" estão confirmados.
        if ($this->gigCosts->isEmpty()) {
            return true;
        }

        // Retorna true apenas se NÃO EXISTIR nenhum custo com is_confirmed = false.
        return $this->gigCosts()->where('is_confirmed', false)->doesntExist();
    }

    // Manteremos os campos `agency_commission_value`, `booker_commission_value`,
    // e `liquid_commission_value` como colunas no banco que serão preenchidas
    // pelo GigObserver (que usará o Service).
    // Os accessors acima com `Calculated` no nome servem para obter o valor
    // "em tempo real" via service, útil para verificação ou se não quisermos
    // depender 100% do valor armazenado. Para exibição geral, usaremos os campos
    // da tabela que o Observer preencheu.

}
