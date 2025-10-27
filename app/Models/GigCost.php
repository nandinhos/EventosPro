<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Usar SoftDeletes aqui
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class GigCost extends Model
{
    use HasFactory, SoftDeletes; // Habilitar SoftDeletes

    protected $fillable = [
        'gig_id',
        'cost_center_id',
        'description',
        'value',
        'currency',
        'expense_date',
        'is_confirmed',
        'is_invoice',
        'confirmed_by',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expense_date' => 'date',
        'is_confirmed' => 'boolean', // Cast para booleano
        'is_invoice' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the Gig that this cost belongs to.
     */
    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    /**
     * Get the Cost Center that this cost belongs to.
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * Get the User who confirmed this cost.
     */
    public function confirmer(): BelongsTo // Nome diferente para evitar conflito com 'user' se existir
    {
        // Especifica a chave estrangeira 'confirmed_by'
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Accessor para obter o valor em BRL.
     */
    public function getValueBrlAttribute(): float
    {
        if (strtoupper($this->currency ?? 'BRL') === 'BRL') {
            return (float) $this->value;
        }

        $exchangeRate = $this->gig->getExchangeRateForCurrency(
            $this->currency,
            Carbon::parse($this->expense_date ?: $this->gig->gig_date ?: today())
        );

        if ($exchangeRate === null) {
            Log::warning("Taxa de câmbio não encontrada para moeda {$this->currency} na data {$this->expense_date} para GigCost ID {$this->id}.");

            return (float) $this->value;
        }

        return (float) $this->value * $exchangeRate;
    }
}
