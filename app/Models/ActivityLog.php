<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // Modelo Eloquent base
use Illuminate\Database\Eloquent\Relations\MorphTo; // Para relacionamentos polimórficos

class ActivityLog extends Model
{
    /**
     * A tabela associada com o modelo.
     *
     * @var string
     */
    protected $table = 'activity_logs'; // Especifica o nome da tabela (boa prática)

    /**
     * Indica se o modelo deve ter timestamps (created_at, updated_at).
     * A nossa migration só adicionou created_at, então desabilitamos updated_at.
     *
     * @var bool
     */
    public $timestamps = false; // Só temos created_at na migration

    /**
     * Define created_at como constante para evitar problemas com o Laravel
     * tentando gerenciar updated_at quando $timestamps é false.
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        // 'created_at' geralmente é gerenciado pelo DB ou automaticamente
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'collection', // Converte a coluna JSON para uma Coleção Laravel
        'created_at' => 'datetime', // Garante que created_at seja um objeto Carbon
    ];

    /**
     * Get the subject of the activity log (the model that was affected).
     * Ex: $log->subject (retorna o modelo Gig, Payment, etc.)
     */
    public function subject(): MorphTo
    {
        // O nome do método 'subject' corresponde ao prefixo usado em nullableMorphs('subject')
        return $this->morphTo();
    }

    /**
     * Get the causer of the activity log (the user who performed the action).
     * Ex: $log->causer (retorna o modelo User)
     */
    public function causer(): MorphTo
    {
        // O nome do método 'causer' corresponde ao prefixo usado em nullableMorphs('causer')
        return $this->morphTo();
    }
}
