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
     * Retorna o valor do cachê original da Gig convertido para BRL.
     * Utiliza uma taxa de câmbio para conversão, se a moeda não for BRL.
     * Este é o "Valor do Contrato em BRL" antes de quaisquer deduções.
     *
     * @return float
     */
    public function getCacheValueBrlAttribute(): float
    {
        if (strtoupper($this->currency ?? 'BRL') === 'BRL') {
            return (float) $this->cache_value;
        }

        // Implementação da busca da taxa de câmbio
        // Por ora, usaremos um placeholder, mas no futuro pode vir de uma API ou config.
        $exchangeRate = $this->getExchangeRateForCurrency($this->currency, Carbon::parse($this->gig_date ?: today()));

        if ($exchangeRate === null) {
            Log::warning("Taxa de câmbio não encontrada para moeda {$this->currency} na data {$this->gig_date} para Gig ID {$this->id}. Retornando valor original sem conversão.");
            return (float) $this->cache_value; // Retorna o valor na moeda original se não houver taxa
        }

        return (float) $this->cache_value * $exchangeRate;
    }

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
    protected function getExchangeRateForCurrency(string $currencyCode, Carbon $date): ?float
    {
        // Lógica de Projeção: Tentar pegar a taxa de câmbio do primeiro pagamento confirmado, se houver.
        // Se não, usar uma taxa de placeholder/configurável.
        $firstConfirmedPaymentWithRate = $this->payments()
            ->whereNotNull('confirmed_at')
            ->where('currency', $currencyCode) // Considerar se a parcela confirmada está na mesma moeda da Gig
            ->whereNotNull('exchange_rate_received_actual') // Usar o novo campo do request que deveria ir pra payment
            ->orderBy('received_date_actual', 'asc')
            ->first();

        if ($firstConfirmedPaymentWithRate && $firstConfirmedPaymentWithRate->exchange_rate_received_actual > 0) {
             // Este campo exchange_rate_received_actual precisa existir na tabela payments
             // O ConfirmPaymentRequest usa 'exchange_rate_received_actual' [cite: 705]
             // O PaymentController::confirm salva em 'exchange_rate'
             // Assumindo que 'exchange_rate' na tabela payments guarda o câmbio do recebimento:
            if(isset($firstConfirmedPaymentWithRate->exchange_rate) && $firstConfirmedPaymentWithRate->exchange_rate > 0) {
                Log::info("[Gig ID {$this->id}] Usando taxa de câmbio do pagamento confirmado {$firstConfirmedPaymentWithRate->id} para {$currencyCode}: {$firstConfirmedPaymentWithRate->exchange_rate}");
                return (float) $firstConfirmedPaymentWithRate->exchange_rate;
            }
        }
        
        // Placeholder/Configurável para projeção se não houver pagamento confirmado com taxa
        $defaultRates = [
            'USD' => (float) (config('app.default_exchange_rates.usd') ?? 5.20),
            'EUR' => (float) (config('app.default_exchange_rates.eur') ?? 5.60),
            'GBP' => (float) (config('app.default_exchange_rates.gbp') ?? 6.20),
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

    // Manteremos os campos `agency_commission_value`, `booker_commission_value`,
    // e `liquid_commission_value` como colunas no banco que serão preenchidas
    // pelo GigObserver (que usará o Service).
    // Os accessors acima com `Calculated` no nome servem para obter o valor
    // "em tempo real" via service, útil para verificação ou se não quisermos
    // depender 100% do valor armazenado. Para exibição geral, usaremos os campos
    // da tabela que o Observer preencheu.

}