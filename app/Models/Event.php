<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importar BelongsTo
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Importar BelongsToMany
use Illuminate\Database\Eloquent\Relations\HasOne; // Importar HasOne

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booker_id',
        'main_artist_id',
        'contract_id', // Permitir associar um contrato ao criar/atualizar um evento
        'name',
        'location_text',
        'event_date',
        'event_time',
        'type',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_date' => 'date',
        // 'event_time' => 'datetime:H:i', // Formata a hora, útil se precisar manipular como objeto datetime
    ];

    /**
     * Get the booker that owns the event.
     * (Um Evento pertence a um Booker)
     */
    public function booker(): BelongsTo
    {
        return $this->belongsTo(Booker::class);
    }

    /**
     * Get the main artist for the event.
     * (Um Evento pertence a um Artista principal)
     */
    public function mainArtist(): BelongsTo
    {
        // Especifica a chave estrangeira porque o nome do método não segue a convenção padrão (artist())
        return $this->belongsTo(Artist::class, 'main_artist_id');
    }

    /**
     * Get the contract associated with the event (if any).
     * (Um Evento pode pertencer a um Contrato)
     */
    public function contract(): BelongsTo
    {
        // Opcional: Laravel assume 'contract_id' como chave estrangeira
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the artists participating in the event (via pivot table).
     * (Um Evento pertence a muitos Artistas)
     */
    public function artists(): BelongsToMany
    {
        // Especifica a tabela pivot 'event_artist'
        return $this->belongsToMany(Artist::class, 'event_artist');
    }

    /**
     * Get the settlement record associated with the event.
     * (Um Evento pode ter um Acerto Financeiro)
     */
    public function settlement(): HasOne
    {
        // Laravel assume 'event_id' como chave estrangeira em 'settlements'
        return $this->hasOne(Settlement::class);
    }
}
