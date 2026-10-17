<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Links a payment to a charge it covers. amount_cents is signed (negative for storno). */
final class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'charge_id',
        'amount_cents',
    ];

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<Charge, $this> */
    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
        ];
    }
}
