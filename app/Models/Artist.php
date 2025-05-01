<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Importar HasMany
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Importar BelongsToMany

class Artist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        // 'contact_email', // Descomente se você manteve estes campos na migration
        // 'contact_phone', // Descomente se você manteve estes campos na migration
        // 'default_fee', // Descomente se você manteve estes campos na migration
    ];

    /**
     * Get the events where this artist is the main artist.
     * (Um Artista pode ser o principal em muitos Eventos)
     */
    public function mainEvents(): HasMany
    {
        // Especifica a chave estrangeira 'main_artist_id' na tabela 'events'
        return $this->hasMany(Event::class, 'main_artist_id');
    }

    /**
     * Get the events that this artist participates in (via pivot table).
     * (Um Artista pode participar de muitos Eventos)
     */
    public function events(): BelongsToMany
    {
        // Especifica a tabela pivot 'event_artist'
        // Laravel infere as chaves estrangeiras 'artist_id' e 'event_id'
        return $this->belongsToMany(Event::class, 'event_artist');
    }
}