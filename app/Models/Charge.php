<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One monthly (or manual one-time) membership charge for a student. The amount is a
 * snapshot (already discounted). Charges are never deleted; a write-off records who/when/why.
 */
final class Charge extends Model
{
    protected $fillable = [
        'student_id',
        'fee_plan_id',
        'student_fee_plan_id',
        'period',
        'amount_cents',
        'due_date',
        'reference',
        'written_off_at',
        'written_off_by',
        'written_off_reason',
        'eracuni_invoice_id',
    ];

    public function isWrittenOff(): bool
    {
        return $this->written_off_at !== null;
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

    /** @return BelongsTo<StudentFeePlan, $this> */
    public function studentFeePlan(): BelongsTo
    {
        return $this->belongsTo(StudentFeePlan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function writtenOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_off_by');
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'due_date' => 'immutable_date',
            'written_off_at' => 'immutable_datetime',
        ];
    }
}
