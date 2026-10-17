<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Recipient / payment data for the Članarine module. Kept in the DB (not .env or
 * code) because the app will later serve multiple clubs. Single row; read via current().
 */
final class ClubBillingSettings extends Model
{
    protected $fillable = [
        'club_name',
        'address',
        'iban',
        'model',
        'reference_rule',
        'purpose_code',
        'due_day',
        'billing_months',
        'currency',
        'note',
    ];

    /** The single settings row, or null before it is seeded / filled in. */
    public static function current(): ?self
    {
        return self::query()->first();
    }

    protected function casts(): array
    {
        return [
            'billing_months' => 'array',
            'due_day' => 'integer',
        ];
    }
}
