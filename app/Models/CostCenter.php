<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    use HasFactory;

    // Não usa SoftDeletes geralmente, centros de custo são mais fixos

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get all the costs associated with this cost center.
     */
    public function gigCosts(): HasMany
    {
        return $this->hasMany(GigCost::class);
    }
}