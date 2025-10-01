<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // <-- Importar HasMany
use Illuminate\Database\Eloquent\SoftDeletes;

class Booker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'default_commission_rate',
        'contact_info',
    ];

    protected $casts = [
        'default_commission_rate' => 'decimal:2',
    ];

    /**
     * Get all gigs associated with the booker.
     * (Um booker pode ter muitas Gigs)
     * Usa 'booker_id' como chave estrangeira padrão na tabela 'gigs'
     */
    public function gigs(): HasMany // <-- Adicionar este método
    {
        return $this->hasMany(Gig::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
