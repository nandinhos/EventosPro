<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DebitNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'gig_id',
        'service_taker_id',
        'year',
        'sequential',
        'number',
        'honorarios',
        'despesas',
        'total',
        'issued_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'year' => 'integer',
        'sequential' => 'integer',
        'honorarios' => 'decimal:2',
        'despesas' => 'decimal:2',
        'total' => 'decimal:2',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // --- Scopes ---

    /**
     * Scope to get only active (non-cancelled) notes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    /**
     * Scope to get only cancelled notes.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->whereNotNull('cancelled_at');
    }

    // --- Relations ---

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function serviceTaker(): BelongsTo
    {
        return $this->belongsTo(ServiceTaker::class);
    }

    // --- Status Helpers ---

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isActive(): bool
    {
        return $this->cancelled_at === null;
    }

    // --- Actions ---

    /**
     * Cancel this debit note with a reason.
     */
    public function cancel(string $reason): bool
    {
        $this->cancelled_at = now();
        $this->cancel_reason = $reason;

        return $this->save();
    }

    /**
     * Reactivate this cancelled note (cancels any other active note for same gig).
     */
    public function activate(): bool
    {
        // Cancel any other active note for this gig
        self::where('gig_id', $this->gig_id)
            ->where('id', '!=', $this->id)
            ->whereNull('cancelled_at')
            ->update([
                'cancelled_at' => now(),
                'cancel_reason' => 'Substituída por nota '.$this->number,
            ]);

        // Reactivate this one
        $this->cancelled_at = null;
        $this->cancel_reason = null;

        return $this->save();
    }

    // --- Auto-numbering ---

    /**
     * Generate the next sequential number for a given year.
     * Uses DB transaction to prevent race conditions.
     *
     * @return array{year: int, sequential: int, number: string}
     */
    public static function generateNumber(?int $year = null): array
    {
        $year = $year ?? now()->year;

        return DB::transaction(function () use ($year) {
            $lastSequential = self::where('year', $year)
                ->lockForUpdate()
                ->max('sequential') ?? 0;

            $nextSequential = $lastSequential + 1;
            $number = str_pad($nextSequential, 3, '0', STR_PAD_LEFT).'/'.$year;

            return [
                'year' => $year,
                'sequential' => $nextSequential,
                'number' => $number,
            ];
        });
    }

    /**
     * Create a new debit note for a gig with auto-generated number.
     * Cancels any existing active note first.
     */
    public static function createForGig(Gig $gig): self
    {
        // Cancel any existing active note
        $existingActive = self::where('gig_id', $gig->id)->active()->first();
        if ($existingActive) {
            $existingActive->cancel('Nova nota gerada');
        }

        $numbering = self::generateNumber();

        return self::create([
            'gig_id' => $gig->id,
            'service_taker_id' => $gig->service_taker_id,
            'year' => $numbering['year'],
            'sequential' => $numbering['sequential'],
            'number' => $numbering['number'],
            'honorarios' => $gig->calculated_agency_gross_commission_brl,
            'despesas' => $gig->total_reimbursable_expenses_brl,
            'total' => $gig->calculated_agency_gross_commission_brl + $gig->total_reimbursable_expenses_brl,
            'issued_at' => now(),
        ]);
    }
}
