<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all the costs associated with this cost center.
     */
    public function gigCosts(): HasMany
    {
        return $this->hasMany(GigCost::class);
    }

    /**
     * Scope a query to only include active cost centers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive cost centers.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
