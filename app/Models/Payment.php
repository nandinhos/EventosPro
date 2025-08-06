<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon; // Importar Carbon

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'gig_id',
        'description', // <-- GARANTIR QUE ESTÁ AQUI
        'due_value',
        'due_date',
        'currency', // <-- GARANTIR QUE ESTÁ AQUI
        'exchange_rate', // <-- GARANTIR QUE ESTÁ AQUI
        'received_value_actual',
        'received_date_actual',
        'confirmed_at',
        'confirmed_by',
        'notes',
    ];
    
    protected $casts = [
        'due_value' => 'decimal:2',
        'due_date' => 'date',
        'received_value_actual' => 'decimal:2',
        'received_date_actual' => 'date',
        'confirmed_at' => 'datetime',
        'exchange_rate' => 'decimal:6', // <-- Adicionado
    ];

    /**
     * Get the gig that this payment belongs to.
     */
    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    /**
     * Get the user who confirmed the payment.
     */
    public function confirmer(): BelongsTo // Renomeado para clareza
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Accessor para obter o status inferido do pagamento.
     * Ex: $payment->inferred_status
     */
    public function getInferredStatusAttribute(): string
    {
        if ($this->confirmed_at) {
            return 'confirmado'; // Ou 'Recebido'
        }
        if ($this->due_date && Carbon::parse($this->due_date)->isPast()) {
            return 'vencido';
        }
        return 'a_vencer';
    }

     /**
     * Accessor para obter a cor do status inferido.
     */
    public function getStatusColorAttribute(): string
    {
         switch ($this->inferred_status) { // Usa o accessor acima
             case 'confirmado': return 'green';
             case 'vencido': return 'red';
             case 'a_vencer': return 'blue';
             default: return 'gray';
         }
    }

    /**
     * Accessor para obter o valor devido em BRL.
     */
    public function getDueValueBrlAttribute(): float
    {
        if (strtoupper($this->currency ?? 'BRL') === 'BRL') {
            return (float) $this->due_value;
        }

        $exchangeRate = $this->exchange_rate ?? $this->gig->getExchangeRateForCurrency(
            $this->currency,
            Carbon::parse($this->due_date ?: today())
        );

        if ($exchangeRate === null) {
            \Log::warning("Taxa de câmbio não encontrada para moeda {$this->currency} na data {$this->due_date} para Payment ID {$this->id}.");
            return (float) $this->due_value;
        }

        return (float) $this->due_value * $exchangeRate;
    }
    
    /**
     * Verifica se o pagamento foi confirmado/recebido
     */
    public function getIsPaidAttribute(): bool
    {
        return !is_null($this->confirmed_at);
    }
}