<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importar BelongsTo

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contract_id',
        'description',
        'value',
        'due_date',
        'paid_at',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime', // Converte para objeto Carbon
        'value' => 'decimal:2',
    ];

    /**
     * Get the contract that the payment belongs to.
     * (Um Pagamento pertence a um Contrato)
     */
    public function contract(): BelongsTo
    {
        // Laravel assume 'contract_id' como chave estrangeira
        return $this->belongsTo(Contract::class);
    }
}