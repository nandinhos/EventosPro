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
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * @property int $id
 * @property int|null $legal_entity_id
 * @property string $contract_data_status
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
 * @property-read \App\Models\LegalEntity|null $legalEntity
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
        'legal_entity_id',
        'contract_data_status',
        'artist_id',
        'booker_id',
        'service_taker_id',
        'contract_number',
        'contract_date',
        'gig_date',
        'location_event_details',
        'cache_value', // Valor original do contrato/cachê
        'currency',    // Moeda original do contrato/cachê
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
        'cache_value' => 'decimal:2',
        'agency_commission_rate' => 'decimal:2',
        'agency_commission_value' => 'decimal:2',
        'booker_commission_rate' => 'decimal:2',
        'booker_commission_value' => 'decimal:2',
        'liquid_commission_value' => 'decimal:2',
        'contract_data_status' => 'string',
    ];

    // --- Relacionamentos ---

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

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

    public function serviceTaker(): BelongsTo
    {
        return $this->belongsTo(ServiceTaker::class);
    }

    /**
     * Get all debit notes for this gig (history).
     */
    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class)->orderByDesc('issued_at');
    }

    /**
     * Get the active (non-cancelled) debit note.
     */
    public function debitNote(): HasOne
    {
        return $this->hasOne(DebitNote::class)->whereNull('cancelled_at');
    }

    /**
     * Check if gig has an active debit note.
     */
    public function hasDebitNote(): bool
    {
        return $this->debitNote()->exists();
    }

    /**
     * Check if gig has any debit notes (including cancelled).
     */
    public function hasAnyDebitNotes(): bool
    {
        return $this->debitNotes()->exists();
    }

    /**
     * Check if workflow is fully complete (pago + ND resolved).
     * Complete if: stage is 'pago' AND (ND not required OR ND exists)
     */
    public function isWorkflowCompleted(): bool
    {
        if ($this->settlement?->settlement_stage !== 'pago') {
            return false;
        }

        // If ND not required, workflow is complete
        if (! $this->settlement->requires_debit_note) {
            return true;
        }

        // If ND required, need active debit note
        return $this->hasDebitNote();
    }

    // --- Instância do Service ---
    protected ?GigFinancialCalculatorService $financialCalculator = null;

    protected function getFinancialCalculator(): GigFinancialCalculatorService
    {
        if ($this->financialCalculator === null) {
            $this->financialCalculator = App::make(GigFinancialCalculatorService::class);
        }

        return $this->financialCalculator;
    }

    // --- Accessors (Atributos Calculados Dinamicamente) ---

    public function getExchangeRateForCurrency(string $currencyCode, Carbon $date): ?float
    {
        $currencyCode = strtoupper($currencyCode);

        if ($currencyCode === 'BRL') {
            return 1.0;
        }

        $firstConfirmedPaymentWithRate = $this->payments()
            ->whereNotNull('confirmed_at')
            ->where('currency', $currencyCode)
            ->whereNotNull('exchange_rate')
            ->where('exchange_rate', '>', 0)
            ->orderBy('received_date_actual', 'asc')
            ->first();

        if ($firstConfirmedPaymentWithRate) {
            return (float) $firstConfirmedPaymentWithRate->exchange_rate;
        }

        $exchangeRateService = app(ExchangeRateService::class);
        $rate = $exchangeRateService->getExchangeRate($currencyCode, $date);

        if ($rate !== null) {
            return $rate;
        }

        return null;
    }

    public function getGrossCashBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateGrossCashBrl($this);
    }

    public function getTotalConfirmedExpensesBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateTotalConfirmedExpensesBrl($this);
    }

    public function getTotalReimbursableExpensesBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateTotalReimbursableExpensesBrl($this);
    }

    public function getCalculatedAgencyGrossCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateAgencyGrossCommissionBrl($this);
    }

    public function getCalculatedBookerCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateBookerCommissionBrl($this);
    }

    public function getCalculatedAgencyNetCommissionBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateAgencyNetCommissionBrl($this);
    }

    public function getCalculatedArtistNetPayoutBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateArtistNetPayoutBrl($this);
    }

    public function getCalculatedArtistInvoiceValueBrlAttribute(): float
    {
        return $this->getFinancialCalculator()->calculateArtistInvoiceValueBrl($this);
    }

    public function getExchangeRateDetails(): array
    {
        if (strtoupper($this->currency ?? 'BRL') === 'BRL') {
            return ['rate' => 1.0, 'type' => 'confirmed'];
        }

        $firstConfirmedPayment = $this->payments()
            ->whereNotNull('confirmed_at')
            ->whereNotNull('exchange_rate')
            ->orderBy('received_date_actual', 'asc')
            ->first();

        if ($firstConfirmedPayment && $firstConfirmedPayment->exchange_rate > 0) {
            return [
                'rate' => (float) $firstConfirmedPayment->exchange_rate,
                'type' => 'confirmed',
            ];
        }

        $defaultRates = config('exchange_rates.default_rates', []);
        $rate = $defaultRates[strtoupper($this->currency)] ?? null;

        return [
            'rate' => $rate,
            'type' => 'projected',
        ];
    }

    public function getTotalReceivedBrlAttribute(): float
    {
        $this->loadMissing('payments');

        return (float) $this->payments
            ->whereNotNull('confirmed_at')
            ->sum(function ($payment) {
                if (strtoupper($payment->currency) === 'BRL') {
                    return $payment->received_value_actual;
                }
                if ($payment->exchange_rate) {
                    return $payment->received_value_actual * $payment->exchange_rate;
                }
                return 0;
            });
    }

    protected function cacheValueBrlDetails(): Attribute
    {
        return Attribute::make(
            get: function () {
                $originalValue = (float) $this->cache_value;
                $gigCurrency = strtoupper($this->currency ?? 'BRL');

                if ($gigCurrency === 'BRL') {
                    return [
                        'value' => $originalValue,
                        'type' => 'confirmed',
                        'rate_used' => 1.0,
                    ];
                }

                $this->loadMissing('payments');

                $confirmedPayments = $this->payments->whereNotNull('confirmed_at');
                $pendingPayments = $this->payments->whereNull('confirmed_at');

                $confirmedBrlValue = $this->total_received_brl;

                $rateForPending = null;
                if ($confirmedPayments->isNotEmpty()) {
                    $lastConfirmed = $confirmedPayments
                        ->where('currency', $gigCurrency)
                        ->sortByDesc('confirmed_at')
                        ->first();
                    if ($lastConfirmed && $lastConfirmed->exchange_rate > 0) {
                        $rateForPending = (float) $lastConfirmed->exchange_rate;
                    }
                }
                if (! $rateForPending) {
                    $defaultRates = config('exchange_rates.default_rates', []);
                    $rateForPending = $defaultRates[$gigCurrency] ?? null;
                }

                $pendingOriginalValue = $pendingPayments->isNotEmpty()
                    ? $pendingPayments->sum('due_value')
                    : ($confirmedPayments->isEmpty() ? $originalValue : 0);

                if ($pendingOriginalValue > 0 && ! $rateForPending) {
                    return [
                        'value' => null,
                        'type' => 'unavailable',
                        'rate_used' => null,
                    ];
                }

                $projectedPendingBrl = $rateForPending ? $pendingOriginalValue * $rateForPending : 0;
                $totalBrlValue = $confirmedBrlValue + $projectedPendingBrl;

                $type = 'projected';
                if ($confirmedPayments->isNotEmpty() && $pendingPayments->isEmpty() && $pendingOriginalValue == 0) {
                    $type = 'confirmed';
                } elseif ($confirmedPayments->isNotEmpty() && ($pendingPayments->isNotEmpty() || $pendingOriginalValue > 0)) {
                    $type = 'hybrid';
                }

                $effectiveRate = $originalValue > 0 ? $totalBrlValue / $originalValue : null;

                return [
                    'value' => $totalBrlValue,
                    'type' => $type,
                    'rate_used' => $effectiveRate,
                    'confirmed_portion_brl' => $confirmedBrlValue,
                    'pending_portion_brl' => $projectedPendingBrl,
                    'rate_for_pending' => $rateForPending,
                ];
            },
        );
    }

    public function getCacheValueBrlAttribute(): ?float
    {
        return $this->cacheValueBrlDetails['value'];
    }

    public function getAreAllCostsConfirmedAttribute(): bool
    {
        if ($this->gigCosts->isEmpty()) {
            return true;
        }

        return $this->gigCosts()->where('is_confirmed', false)->doesntExist();
    }
}
