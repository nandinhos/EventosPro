<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany; // Importar MorphToMany

class Tag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug', // Permitir preenchimento do slug
        'type',
    ];

    /**
     * Get all of the gigs that are assigned this tag.
     */
    public function gigs(): MorphToMany
    {
        // 'taggable' é o nome da relação polimórfica definida na migration taggables
        // O segundo argumento é o nome da relação ('taggable')
        return $this->morphedByMany(Gig::class, 'taggable');
    }

    /**
     * Get all of the artists that are assigned this tag.
     */
    public function artists(): MorphToMany
    {
        return $this->morphedByMany(Artist::class, 'taggable');
    }

    // Adicione outros modelos que podem ter tags aqui no futuro, se necessário
    // Exemplo:
    // public function payments(): MorphToMany
    // {
    //     return $this->morphedByMany(Payment::class, 'taggable');
    // }
}
