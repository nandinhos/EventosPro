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
        // Campos de rastreio de reembolso
        'reimbursement_stage',
        'reimbursement_proof_type',
        'reimbursement_proof_file',
        'reimbursement_proof_received_at',
        'reimbursement_value_confirmed',
        'reimbursement_confirmed_at',
        'reimbursement_confirmed_by',
        'reimbursement_notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expense_date' => 'date',
        'is_confirmed' => 'boolean',
        'is_invoice' => 'boolean',
        'confirmed_at' => 'datetime',
        'reimbursement_proof_received_at' => 'datetime',
        'reimbursement_value_confirmed' => 'decimal:2',
        'reimbursement_confirmed_at' => 'datetime',
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
            // Log::warning("Taxa de câmbio não encontrada para moeda {$this->currency} na data {$this->expense_date} para GigCost ID {$this->id}.");

            return (float) $this->value;
        }

        return (float) $this->value * $exchangeRate;
    }

    // ====================================================
    // CONSTANTES DE ESTÁGIO DE REEMBOLSO (Simplificado: 2 estágios)
    // ====================================================
    
    public const STAGE_AGUARDANDO_COMPROVANTE = 'aguardando_comprovante';
    public const STAGE_PAGO = 'pago';
    
    // Estágios válidos (novo workflow simplificado)
    public const REIMBURSEMENT_STAGES = [
        self::STAGE_AGUARDANDO_COMPROVANTE => 'Aguardando Comprovante',
        self::STAGE_PAGO => 'Pago',
    ];
    
    // Mapeamento de estágios legados para o novo workflow
    public const LEGACY_STAGE_MAPPING = [
        'comprovante_recebido' => self::STAGE_PAGO,
        'conferido' => self::STAGE_PAGO,
        'reembolsado' => self::STAGE_PAGO,
    ];

    public const REIMBURSEMENT_PROOF_TYPES = [
        'recibo' => 'Recibo',
        'nf' => 'Nota Fiscal',
        'transferencia' => 'Comprovante de Transferência',
        'outro' => 'Outro',
    ];
    
    /**
     * Accessor que normaliza estágios legados para o novo workflow
     */
    public function getEffectiveReimbursementStageAttribute(): string
    {
        $stage = $this->reimbursement_stage ?? self::STAGE_AGUARDANDO_COMPROVANTE;
        
        // Mapeia estágios legados para 'pago'
        if (isset(self::LEGACY_STAGE_MAPPING[$stage])) {
            return self::LEGACY_STAGE_MAPPING[$stage];
        }
        
        return $stage;
    }

    // ====================================================
    // SCOPES
    // ====================================================

    /**
     * Scope para despesas reembolsáveis (is_invoice = true)
     */
    public function scopeReimbursable($query)
    {
        return $query->where('is_invoice', true);
    }

    /**
     * Scope para filtrar por estágio de reembolso
     */
    public function scopeReimbursementStage($query, string $stage)
    {
        return $query->where('reimbursement_stage', $stage);
    }

    // ====================================================
    // ACCESSORS
    // ====================================================

    /**
     * Accessor para label do estágio de reembolso (usa estágio normalizado)
     */
    public function getReimbursementStageLabelAttribute(): string
    {
        return self::REIMBURSEMENT_STAGES[$this->effective_reimbursement_stage] ?? 'N/A';
    }

    /**
     * Accessor para label do tipo de comprovante
     */
    public function getReimbursementProofTypeLabelAttribute(): string
    {
        return self::REIMBURSEMENT_PROOF_TYPES[$this->reimbursement_proof_type] ?? 'N/A';
    }

    /**
     * Retorna o usuário que confirmou o reembolso
     */
    public function reimbursementConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reimbursement_confirmed_by');
    }
}
