<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany; // Para Tags
// use Spatie\Activitylog\Traits\LogsActivity; // Para auditoria com pacote
// use Spatie\Activitylog\LogOptions; // Para auditoria com pacote
use Illuminate\Database\Eloquent\SoftDeletes;

class Gig extends Model
{
    use HasFactory, SoftDeletes; // Adiciona SoftDeletes
    // use HasFactory, SoftDeletes, LogsActivity; // Use esta linha se instalar o pacote de activity log

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'artist_id',
        'booker_id',
        'contract_number',
        'contract_date',
        'gig_date',
        'location_event_details',
        'cache_value', // <-- Mudou de total_value
        'currency',
        // 'exchange_rate', // Removido
        // 'cache_value_brl', // Removido
        'expenses_value_brl',
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
        // 'file_path' // Removido conforme migration final
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contract_date' => 'date',
        'gig_date' => 'date',
        'cache_value' => 'decimal:2',
        //'cache_value_brl' => 'decimal:2',
        'expenses_value_brl' => 'decimal:2',
        //'exchange_rate' => 'decimal:6',
        'agency_commission_value' => 'decimal:2',
        'booker_commission_value' => 'decimal:2',
        'liquid_commission_value' => 'decimal:2',
        'agency_commission_rate' => 'decimal:2',
        'booker_commission_rate' => 'decimal:2',
    ];

    /**
     * Get the artist associated with the gig.
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    /**
     * Get the booker associated with the gig (can be null).
     */
    public function booker(): BelongsTo
    {
        // Como booker_id pode ser nulo, o relacionamento pode retornar null
        return $this->belongsTo(Booker::class);
    }

    /**
     * Get all payments received for this gig.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the final settlement record for this gig.
     */
    public function settlement(): HasOne // Uma gig tem um acerto final
    {
        return $this->hasOne(Settlement::class);
    }

    /**
     * Get all tags associated with the gig.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
      * Opcional: Configuração para spatie/laravel-activitylog
      */
    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logOnly([
    //              'artist_id', 'booker_id', 'gig_date', 'location_event_details', 'cache_value_brl',
    //              'payment_status', 'artist_payment_status', 'booker_payment_status'
    //              // Adicione outros campos importantes para logar
    //         ])
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs();
    // }

    // --- Accessors & Mutators (Opcionais, mas úteis) ---

    /**
     * Calcula a comissão líquida dinamicamente se não estiver salva.
     * Exemplo: $gig->liquid_commission
     */
    // public function getLiquidCommissionAttribute(): ?float
    // {
    //     // Retorna o valor salvo se existir, senão calcula
    //     if (isset($this->attributes['liquid_commission_value'])) {
    //         return (float) $this->attributes['liquid_commission_value'];
    //     }
    //     // Calcula: Comissão da Agência - Comissão do Booker
    //     // Precisaria de lógica para calcular agency_commission_value e booker_commission_value se não estiverem salvos
    //     // Esta lógica pode ficar mais complexa, talvez melhor em um Service.
    //     $agencyCommission = $this->agency_commission_value ?? 0; // Precisa calcular se for percentual
    //     $bookerCommission = $this->booker_commission_value ?? 0; // Precisa calcular se for percentual
    //     return $agencyCommission - $bookerCommission;
    // }

}