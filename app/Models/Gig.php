<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon; // Importar Carbon

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
        'cache_value', // Valor original
        'currency',    // Moeda original
        // exchange_rate e cache_value_brl NÃO SÃO COLUNAS
        'agency_commission_type',
        'agency_commission_rate',
        'agency_commission_value', // Valor final BRL da comissão agência
        'booker_commission_type',
        'booker_commission_rate',
        'booker_commission_value', // Valor final BRL da comissão booker
        'liquid_commission_value', // Valor final BRL da comissão líquida
        'payment_status',
        'artist_payment_status',
        'booker_payment_status',
        'contract_status',
        'notes',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'gig_date' => 'date',
        'cache_value' => 'decimal:2',
        // Sem casts para exchange_rate e cache_value_brl
        'agency_commission_rate' => 'decimal:2',
        'agency_commission_value' => 'decimal:2',
        'booker_commission_rate' => 'decimal:2',
        'booker_commission_value' => 'decimal:2',
        'liquid_commission_value' => 'decimal:2',
    ];

    // --- Relacionamentos ---
    public function artist(): BelongsTo { return $this->belongsTo(Artist::class); }
    public function booker(): BelongsTo { return $this->belongsTo(Booker::class)->withDefault(); } // withDefault para evitar erro se booker_id for null
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function settlement(): HasOne { return $this->hasOne(Settlement::class); }
    public function tags(): MorphToMany { return $this->morphToMany(Tag::class, 'taggable'); }
    public function costs(): HasMany { return $this->hasMany(GigCost::class); }

    // --- Accessors (Atributos Calculados Dinamicamente) ---

    /**
     * Retorna o valor do cachê convertido para BRL.
     * Usa uma função auxiliar para obter a taxa de câmbio.
     */
    public function getCacheValueBrlAttribute(): float
    {
        if (strtoupper($this->currency ?? 'BRL') === 'BRL') {
            return (float) $this->cache_value;
        }
        // Obtém a data para buscar a cotação (data da gig ou data atual se gig_date for nula)
        $referenceDate = $this->gig_date ? Carbon::parse($this->gig_date) : Carbon::today();
        $exchangeRate = $this->getExchangeRateForCurrency($this->currency, $referenceDate);

        // Se não encontrar taxa, retorna o valor original (ou pode lançar erro/default)
        return $exchangeRate ? (float) $this->cache_value * $exchangeRate : (float) $this->cache_value;
    }

    /**
     * Calcula o total das despesas confirmadas em BRL para esta Gig.
     * Assume que as despesas em gig_costs já estão em BRL ou precisam de conversão.
     */
    public function getConfirmedExpensesTotalBrlAttribute(): float
    {
        // TODO: Se gig_costs.currency puder ser diferente de BRL, adicionar lógica de conversão aqui.
        return (float) $this->costs()->where('is_confirmed', true)->sum('value');
    }

    /**
     * Calcula o valor líquido do cachê (Cachê BRL - Despesas Confirmadas BRL).
     */
    public function getNetCacheValueBrlAttribute(): float
    {
        $cacheBrl = $this->cache_value_brl; // Chama o accessor getCacheValueBrlAttribute()
        $expensesBrl = $this->confirmed_expenses_total_brl; // Chama o accessor
        return (float) max(0, $cacheBrl - $expensesBrl);
    }

    /**
     * Calcula a base de comissão (usando o valor líquido do cachê).
     */
    public function getCommissionBaseBrlAttribute(): float
    {
        return $this->net_cache_value_brl; // Usa o valor líquido como base para comissões
    }

    /**
     * Função auxiliar para buscar taxa de câmbio (IMPLEMENTAR LÓGICA REAL).
     */
    protected function getExchangeRateForCurrency(string $currencyCode, Carbon $date): ?float
    {
        // EXEMPLO COM VALORES FIXOS PARA TESTE - SUBSTITUIR POR LÓGICA REAL
        $rates = [
            'USD' => 5.00,
            'EUR' => 5.50,
            'GPB' => 6.00,
        ];
        return $rates[strtoupper($currencyCode)] ?? null; // Retorna a taxa ou null
    }

    /**
     * Accessor para obter o valor da comissão da agência.
     * Calcula dinamicamente se o tipo for 'percent'.
     */
    public function getAgencyCommissionValueAttribute($value): ?float // Recebe o valor da coluna do DB
    {
        if (strtoupper($this->agency_commission_type ?? '') === 'percent' && isset($this->agency_commission_rate)) {
            // Calcula sobre a base de comissão (Cachê BRL - Despesas Confirmadas BRL)
            $base = $this->commission_base_brl; // Usa o accessor que já calcula isso
            return (float) (($base * $this->agency_commission_rate) / 100);
        }
        // Se for 'fixed' ou tipo não definido, retorna o valor que está no banco (ou null)
        return $value !== null ? (float) $value : null;
    }

    /**
     * Accessor para obter o valor da comissão do booker.
     * Calcula dinamicamente se o tipo for 'percent'.
     */
    public function getBookerCommissionValueAttribute($value): ?float
    {
        if (strtoupper($this->booker_commission_type ?? '') === 'percent' && isset($this->booker_commission_rate)) {
            $base = $this->commission_base_brl;
            return (float) (($base * $this->booker_commission_rate) / 100);
        }
        return $value !== null ? (float) $value : null;
    }

    /**
     * Accessor para obter o valor da comissão líquida da agência.
     * (Comissão da Agência - Comissão do Booker)
     */
    public function getLiquidCommissionValueAttribute($value): ?float
    {
        // Usa os accessors para pegar os valores calculados/corretos das comissões
        $agencyComm = $this->agency_commission_value; // Chama getAgencyCommissionValueAttribute
        $bookerComm = $this->booker_commission_value; // Chama getBookerCommissionValueAttribute

        return (float) (($agencyComm ?? 0) - ($bookerComm ?? 0));
    }
}