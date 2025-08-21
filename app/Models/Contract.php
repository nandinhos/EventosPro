<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Importar HasMany
use Illuminate\Database\Eloquent\Relations\HasOne; // Importar HasOne

class Contract extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_number',
        'contract_date',
        'value',
        'currency',
        'status',
        'file_path',
    ];

    /**
     * The attributes that should be cast.
     * (Converte automaticamente os tipos de dados)
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contract_date' => 'date', // Converte para objeto Carbon/Date
        'value' => 'decimal:2', // Garante que o valor seja tratado como decimal com 2 casas
    ];

    /**
     * Get the payments associated with the contract.
     * (Um Contrato pode ter muitos Pagamentos)
     */
    public function payments(): HasMany
    {
        // Laravel assume 'contract_id' como chave estrangeira em 'payments'
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the event associated with the contract (if any).
     * (Um Contrato pode ter um Evento associado via 'contract_id' na tabela 'events')
     */
    public function event(): HasOne
    {
        // Laravel assume 'contract_id' como chave estrangeira em 'events'
        return $this->hasOne(Event::class);
    }
}
