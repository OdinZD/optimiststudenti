<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A membership payment. Amount is signed (negative for a storno/reversal). Payments
 * are never deleted; a correction is a reversing payment (reversed_payment_id).
 */
final class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'amount_cents',
        'paid_at',
        'method',
        'source',
        'bank_reference',
        'note',
        'recorded_by',
        'received_by',
        'reversed_payment_id',
    ];

    /** This row reverses another payment (storno). */
    public function isReversal(): bool
    {
        return $this->reversed_payment_id !== null;
    }

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_at' => 'immutable_date',
        ];
    }
}
