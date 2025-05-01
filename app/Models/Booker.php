<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Importar HasMany

class Booker extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * (Quais colunas podem ser preenchidas via Booker::create([...]))
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        // 'contact_email', // Descomente se você manteve estes campos na migration
        // 'contact_phone', // Descomente se você manteve estes campos na migration
    ];

    /**
     * Get the events associated with the booker.
     * (Um Booker pode ter muitos Eventos)
     */
    public function events(): HasMany
    {
        // O Laravel assume booker_id como chave estrangeira em 'events'
        return $this->hasMany(Event::class);
    }
}