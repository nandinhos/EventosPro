<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // <-- Importar HasMany
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Artist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_info',
    ];

    /**
     * Get all gigs associated with the artist.
     * (Um artista pode ter muitas Gigs)
     * Usa 'artist_id' como chave estrangeira padrão na tabela 'gigs'
     */
    public function gigs(): HasMany // <-- Adicionar este método
    {
        return $this->hasMany(Gig::class);
    }

    /**
     * Get all tags associated with the artist.
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}