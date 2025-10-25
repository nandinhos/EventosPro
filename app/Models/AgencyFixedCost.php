<?php

namespace App\Models;

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
        'category',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'monthly_value' => 'decimal:2',
        'reference_month' => 'date',
        'is_active' => 'boolean',
    ];

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
}
