<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTaker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization',
        'document',
        'street',
        'postal_code',
        'city',
        'country',
        'company_phone',
        'contact',
        'email',
        'phone',
    ];

    /**
     * Get all gigs associated with this service taker.
     */
    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class);
    }

    /**
     * Get the full formatted address.
     */
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->street,
            $this->postal_code,
            $this->city,
            $this->country,
        ])->filter()->implode(', ');
    }

    /**
     * Get the formatted document with label (CPF/CNPJ/Doc).
     * CPF: 000.000.000-00 (11 digits)
     * CNPJ: 00.000.000/0000-00 (14 digits)
     * Other: returns as-is with "Doc:" label
     */
    public function getFormattedDocumentAttribute(): ?string
    {
        if (empty($this->document)) {
            return null;
        }

        // Remove non-numeric characters for analysis
        $digits = preg_replace('/\D/', '', $this->document);
        $length = strlen($digits);

        // CPF: 11 digits -> CPF: 000.000.000-00
        if ($length === 11) {
            $formatted = substr($digits, 0, 3).'.'.
                         substr($digits, 3, 3).'.'.
                         substr($digits, 6, 3).'-'.
                         substr($digits, 9, 2);

            return 'CPF: '.$formatted;
        }

        // CNPJ: 14 digits -> CNPJ: 00.000.000/0000-00
        if ($length === 14) {
            $formatted = substr($digits, 0, 2).'.'.
                         substr($digits, 2, 3).'.'.
                         substr($digits, 5, 3).'/'.
                         substr($digits, 8, 4).'-'.
                         substr($digits, 12, 2);

            return 'CNPJ: '.$formatted;
        }

        // Other documents (foreign, etc.): return as-is with Doc label
        return 'Doc: '.$this->document;
    }
}
