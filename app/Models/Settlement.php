<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Settlement extends Model
{
    use HasFactory, SoftDeletes;

    // Constantes para os estágios do workflow de fechamento
    public const STAGE_AGUARDANDO_CONFERENCIA = 'aguardando_conferencia';
    public const STAGE_FECHAMENTO_ENVIADO = 'fechamento_enviado';
    public const STAGE_DOCUMENTACAO_RECEBIDA = 'documentacao_recebida';
    public const STAGE_PAGO = 'pago';

    // Labels amigáveis para exibição
    public const STAGE_LABELS = [
        self::STAGE_AGUARDANDO_CONFERENCIA => 'Aguardando Conferência',
        self::STAGE_FECHAMENTO_ENVIADO => 'Ag. NF/Recibo',
        self::STAGE_DOCUMENTACAO_RECEBIDA => 'Pronto p/ Pagar',
        self::STAGE_PAGO => 'Pago',
    ];

    // Cores para badges por estágio
    public const STAGE_COLORS = [
        self::STAGE_AGUARDANDO_CONFERENCIA => 'gray',
        self::STAGE_FECHAMENTO_ENVIADO => 'blue',
        self::STAGE_DOCUMENTACAO_RECEBIDA => 'yellow',
        self::STAGE_PAGO => 'green',
    ];

    // Ícones para cada estágio
    public const STAGE_ICONS = [
        self::STAGE_AGUARDANDO_CONFERENCIA => 'clipboard-check',
        self::STAGE_FECHAMENTO_ENVIADO => 'paper-plane',
        self::STAGE_DOCUMENTACAO_RECEBIDA => 'file-invoice',
        self::STAGE_PAGO => 'check-circle',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gig_id',
        'settlement_date',
        'artist_payment_value',
        'artist_payment_paid_at',
        'artist_payment_proof',
        'booker_commission_value_paid',
        'booker_commission_paid_at',
        'booker_commission_proof',
        'notes',
        // Novos campos de rastreamento
        'settlement_stage',
        'settlement_sent_at',
        'documentation_received_at',
        'documentation_type',
        'documentation_number',
        'documentation_file_path',
        'communication_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settlement_date' => 'date',
        'artist_payment_value' => 'decimal:2',
        'artist_payment_paid_at' => 'date',
        'booker_commission_value_paid' => 'decimal:2',
        'booker_commission_paid_at' => 'date',
        // Novos casts
        'settlement_sent_at' => 'datetime',
        'documentation_received_at' => 'datetime',
    ];

    // --- Relacionamentos ---

    /**
     * Obtém a Gig à qual este acerto pertence.
     */
    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    // --- Accessors ---

    /**
     * Retorna o label amigável do estágio atual.
     */
    public function getStageLabelAttribute(): string
    {
        return self::STAGE_LABELS[$this->settlement_stage] ?? 'Desconhecido';
    }

    /**
     * Retorna a cor do badge para o estágio atual.
     */
    public function getStageColorAttribute(): string
    {
        return self::STAGE_COLORS[$this->settlement_stage] ?? 'gray';
    }

    /**
     * Retorna o ícone FontAwesome para o estágio atual.
     */
    public function getStageIconAttribute(): string
    {
        return self::STAGE_ICONS[$this->settlement_stage] ?? 'question-circle';
    }

    // --- Scopes ---

    /**
     * Filtra por estágio específico.
     */
    public function scopeByStage(Builder $query, string $stage): Builder
    {
        return $query->where('settlement_stage', $stage);
    }

    /**
     * Fechamentos aguardando conferência.
     */
    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->where('settlement_stage', self::STAGE_AGUARDANDO_CONFERENCIA);
    }

    /**
     * Fechamentos com envio pendente de documentação.
     */
    public function scopePendingDocument(Builder $query): Builder
    {
        return $query->where('settlement_stage', self::STAGE_FECHAMENTO_ENVIADO);
    }

    /**
     * Fechamentos prontos para pagamento.
     */
    public function scopeReadyToPay(Builder $query): Builder
    {
        return $query->where('settlement_stage', self::STAGE_DOCUMENTACAO_RECEBIDA);
    }

    /**
     * Fechamentos já pagos.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('settlement_stage', self::STAGE_PAGO);
    }

    /**
     * Fechamentos não finalizados (todos exceto 'pago').
     */
    public function scopeNotPaid(Builder $query): Builder
    {
        return $query->where('settlement_stage', '!=', self::STAGE_PAGO);
    }

    // --- Métodos de Transição de Estado ---

    /**
     * Avança para o próximo estágio válido.
     */
    public function advanceStage(): bool
    {
        $stages = [
            self::STAGE_AGUARDANDO_CONFERENCIA,
            self::STAGE_FECHAMENTO_ENVIADO,
            self::STAGE_DOCUMENTACAO_RECEBIDA,
            self::STAGE_PAGO,
        ];

        $currentIndex = array_search($this->settlement_stage, $stages);
        if ($currentIndex === false || $currentIndex >= count($stages) - 1) {
            return false;
        }

        $this->settlement_stage = $stages[$currentIndex + 1];

        return true;
    }

    /**
     * Verifica se pode avançar para o estágio de envio.
     */
    public function canSendSettlement(): bool
    {
        return $this->settlement_stage === self::STAGE_AGUARDANDO_CONFERENCIA;
    }

    /**
     * Verifica se pode registrar recebimento de documentação.
     */
    public function canReceiveDocumentation(): bool
    {
        return $this->settlement_stage === self::STAGE_FECHAMENTO_ENVIADO;
    }

    /**
     * Verifica se pode ser marcado como pago.
     */
    public function canMarkAsPaid(): bool
    {
        return $this->settlement_stage === self::STAGE_DOCUMENTACAO_RECEBIDA;
    }
}
