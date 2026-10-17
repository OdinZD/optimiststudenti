<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeePlanType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assigns a tariff to a student for a period, with an optional discount (percentage
 * OR fixed cents — never both) and the payer's name. valid_until null means active.
 */
final class StudentFeePlan extends Model
{
    protected $fillable = [
        'student_id',
        'fee_plan_id',
        'discount_percent',
        'discount_cents',
        'valid_from',
        'valid_until',
        'payer_name',
        'created_by',
    ];

    /** Discount applied to a base amount (cents), rounded to the cent. */
    public function discountAmountCents(int $baseCents): int
    {
        if ($this->discount_percent !== null) {
            return (int) round($baseCents * $this->discount_percent / 100);
        }

        if ($this->discount_cents !== null) {
            return min($this->discount_cents, $baseCents);
        }

        return 0;
    }

    /** Tariff amount minus discount (never below zero). Needs the feePlan relation. */
    public function effectiveAmountCents(): int
    {
        $base = $this->feePlan->amount_cents;

        return max(0, $base - $this->discountAmountCents($base));
    }

    /**
     * Rule: a student may have at most one active monthly tariff overlapping a period.
     * Overlap = existing.from <= new.until AND (existing.until is null OR >= new.from).
     */
    public static function monthlyOverlapExists(int $studentId, string $from, ?string $until, ?int $ignoreId = null): bool
    {
        return self::query()
            ->where('student_id', $studentId)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereHas('feePlan', fn ($q) => $q->where('type', FeePlanType::Monthly->value))
            ->where('valid_from', '<=', $until ?? '9999-12-31')
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $from))
            ->exists();
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<FeePlan, $this> */
    public function feePlan(): BelongsTo
    {
        return $this->belongsTo(FeePlan::class);
    }

    protected function casts(): array
    {
        return [
            'discount_percent' => 'integer',
            'discount_cents' => 'integer',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
        ];
    }
}
