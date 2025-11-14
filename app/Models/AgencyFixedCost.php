<?php

namespace App\Models;

use App\Enums\AgencyCostType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model para Custos Fixos da Agência (CFM - Custo Fixo Médio).
 * Armazena custos operacionais mensais da agência para cálculo do Resultado Operacional.
 */
class AgencyFixedCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'description',
        'monthly_value',
        'reference_month',
        'cost_center_id',
        'due_date',
        'cost_type',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'monthly_value' => 'decimal:2',
        'reference_month' => 'date',
        'due_date' => 'date',
        'is_active' => 'boolean',
        'cost_type' => AgencyCostType::class,
    ];

    /**
     * Get the cost center that owns the agency fixed cost.
     */
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMonth($query, string $yearMonth)
    {
        return $query->where('reference_month', 'LIKE', $yearMonth.'%');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('cost_type', $type);
    }

    public function scopeDueInMonth($query, string $yearMonth)
    {
        return $query->where('due_date', 'LIKE', $yearMonth.'%');
    }

    public function scopeOperational($query)
    {
        return $query->where('cost_type', AgencyCostType::OPERACIONAL);
    }

    public function scopeAdministrative($query)
    {
        return $query->where('cost_type', AgencyCostType::ADMINISTRATIVO);
    }
}
