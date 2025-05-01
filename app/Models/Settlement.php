<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importar BelongsTo

class Settlement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'settlement_date',
        'artist_net_amount',
        'agency_commission',
        'booker_commission',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settlement_date' => 'date',
        'artist_net_amount' => 'decimal:2',
        'agency_commission' => 'decimal:2',
        'booker_commission' => 'decimal:2',
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