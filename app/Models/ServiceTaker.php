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
        'state_registration',      // Inscrição Estadual (IE)
        'municipal_registration',  // Inscrição Municipal (IM)
        'is_international',        // Documento estrangeiro
        'street',
        'postal_code',
        'city',
        'state',
        'country',
        'company_phone',
        'contact',
        'email',
        'phone',
    ];

    protected $casts = [
        'is_international' => 'boolean',
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
            $this->state,
            $this->country,
        ])->filter()->implode(', ');
    }

    /**
     * Get the formatted document with label (CPF/CNPJ/Doc).
     */
    public function getFormattedDocumentAttribute(): ?string
    {
        if (empty($this->document)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->document);
        $length = strlen($digits);

        if ($length === 11) {
            $formatted = substr($digits, 0, 3).'.'.
                         substr($digits, 3, 3).'.'.
                         substr($digits, 6, 3).'-'.
                         substr($digits, 9, 2);

            return 'CPF: '.$formatted;
        }

        if ($length === 14) {
            $formatted = substr($digits, 0, 2).'.'.
                         substr($digits, 2, 3).'.'.
                         substr($digits, 5, 3).'/'.
                         substr($digits, 8, 4).'-'.
                         substr($digits, 12, 2);

            return 'CNPJ: '.$formatted;
        }

        return 'Doc: '.$this->document;
    }

    /**
     * Validate CPF using check digits algorithm.
     */
    public function validateCpf(): bool
    {
        $cpf = preg_replace('/\D/', '', $this->document);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // First check digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $d1 = 11 - ($sum % 11);
        $d1 = $d1 >= 10 ? 0 : $d1;

        // Second check digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $d2 = 11 - ($sum % 11);
        $d2 = $d2 >= 10 ? 0 : $d2;

        return $cpf[9] == $d1 && $cpf[10] == $d2;
    }

    /**
     * Validate CNPJ using check digits algorithm.
     */
    public function validateCnpj(): bool
    {
        $cnpj = preg_replace('/\D/', '', $this->document);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // First check digit
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $weights1[$i];
        }
        $d1 = $sum % 11;
        $d1 = $d1 < 2 ? 0 : 11 - $d1;

        // Second check digit
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $weights2[$i];
        }
        $d2 = $sum % 11;
        $d2 = $d2 < 2 ? 0 : 11 - $d2;

        return $cnpj[12] == $d1 && $cnpj[13] == $d2;
    }

    /**
     * Get document validation status.
     * Returns: sem_documento, internacional, invalido, ok
     */
    public function getDocumentStatusAttribute(): string
    {
        if (empty($this->document)) {
            return 'sem_documento';
        }

        if ($this->is_international) {
            return 'internacional';
        }

        $digits = preg_replace('/\D/', '', $this->document);
        $length = strlen($digits);

        if ($length === 11) {
            return $this->validateCpf() ? 'ok' : 'invalido';
        }

        if ($length === 14) {
            return $this->validateCnpj() ? 'ok' : 'invalido';
        }

        // Other lengths are considered invalid for BR docs
        return 'invalido';
    }

    /**
     * Get badge configuration for document status.
     */
    public function getDocumentStatusBadgeAttribute(): array
    {
        return match ($this->document_status) {
            'sem_documento' => ['label' => 'Sem Doc', 'color' => 'gray'],
            'internacional' => ['label' => 'Internacional', 'color' => 'blue'],
            'invalido' => ['label' => 'Inválido', 'color' => 'red'],
            'ok' => ['label' => 'OK', 'color' => 'green'],
            default => ['label' => '?', 'color' => 'gray'],
        };
    }
}
