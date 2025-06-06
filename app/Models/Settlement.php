<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importar BelongsTo
use Illuminate\Database\Eloquent\SoftDeletes;

class Settlement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gig_id',
        'settlement_date',
        'artist_payment_value',         // <-- NOVO
        'artist_payment_paid_at',       // <-- NOVO
        'artist_payment_proof',
        'booker_commission_value_paid', // <-- NOVO
        'booker_commission_paid_at',    // <-- NOVO
        'booker_commission_proof',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settlement_date' => 'date',
        'artist_payment_value' => 'decimal:2',         // <-- NOVO
        'artist_payment_paid_at' => 'date',           // <-- NOVO
        'booker_commission_value_paid' => 'decimal:2', // <-- NOVO
        'booker_commission_paid_at' => 'date',        // <-- NOVO
    ];

    /**
     * Get the event that the settlement belongs to.
     * (Um Acerto pertence a um Evento)
     */
    public function event(): BelongsTo
    {
        // Laravel assume 'event_id' como chave estrangeira
        return $this->belongsTo(Event::class);
    }
}