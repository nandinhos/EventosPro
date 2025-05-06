<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gig extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * REMOVIDO: expenses_value_brl
     */
    protected $fillable = [
        'artist_id',
        'booker_id',
        'contract_number',
        'contract_date',
        'gig_date',
        'location_event_details',
        'cache_value', // Valor original na moeda original
        'currency',
        'exchange_rate',           // Mantido (migration adiciona/remove)
        'cache_value_brl',         // Mantido (migration adiciona/remove)
        'agency_commission_type',
        'agency_commission_rate',
        'agency_commission_value',
        'booker_commission_type',
        'booker_commission_rate',
        'booker_commission_value',
        'liquid_commission_value',
        'payment_status',
        'artist_payment_status',
        'booker_payment_status',
        'contract_status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     * REMOVIDO: expenses_value_brl
     */
    protected $casts = [
        'contract_date' => 'date',
        'gig_date' => 'date',
        'cache_value' => 'decimal:2',
        'cache_value_brl' => 'decimal:2',    // Mantido
        'exchange_rate' => 'decimal:6',      // Mantido
        'agency_commission_value' => 'decimal:2',
        'booker_commission_value' => 'decimal:2',
        'liquid_commission_value' => 'decimal:2',
        'agency_commission_rate' => 'decimal:2',
        'booker_commission_rate' => 'decimal:2',
    ];

    // --- Relacionamentos ---
    public function artist(): BelongsTo { return $this->belongsTo(Artist::class); }
    public function booker(): BelongsTo { return $this->belongsTo(Booker::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function settlement(): HasOne { return $this->hasOne(Settlement::class); }
    public function tags(): MorphToMany { return $this->morphToMany(Tag::class, 'taggable'); }

    /**
     * Get all the costs associated with this gig.
     * NOVO RELACIONAMENTO
     */
    public function costs(): HasMany
    {
        return $this->hasMany(GigCost::class);
    }

    // --- Accessors (Atributos Calculados) ---

    /**
     * Calcula o total das despesas confirmadas em BRL para esta Gig.
     * Uso: $gig->confirmed_expenses_total_brl
     */
    public function getConfirmedExpensesTotalBrlAttribute(): float
    {
        // Soma o 'value' de todos os GigCost relacionados onde 'is_confirmed' é true
        // TODO: Adicionar conversão se gig_costs.currency != 'BRL'
        return (float) $this->costs()->where('is_confirmed', true)->sum('value');
    }

    /**
     * Calcula a base de comissão (Cachê BRL - Despesas Confirmadas BRL).
     * Uso: $gig->commission_base_brl
     */
    public function getCommissionBaseBrlAttribute(): float
    {
        $base = $this->cache_value_brl ?? 0;
        // Usa o accessor definido acima para pegar o total das despesas confirmadas
        $expenses = $this->confirmed_expenses_total_brl;
        return (float) max(0, $base - $expenses); // Garante que não seja negativo e retorna float
    }
}