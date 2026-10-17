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
        'invoice_line_template',
        'reminder_days_after_due',
        'eracuni_username',
        'eracuni_secret_key',
        'eracuni_token',
        'eracuni_endpoint',
        'eracuni_environment',
    ];

    /** The single settings row, or null before it is seeded / filled in. */
    public static function current(): ?self
    {
        return self::query()->first();
    }

    public function isProduction(): bool
    {
        return $this->eracuni_environment === 'production';
    }

    public function hasEracuniCredentials(): bool
    {
        return $this->eracuni_username !== null && $this->eracuni_secret_key !== null;
    }

    protected function casts(): array
    {
        return [
            'billing_months' => 'array',
            'due_day' => 'integer',
            'reminder_days_after_due' => 'integer',
            // eRačuni vjerodajnice — šifrirane u bazi (AES preko APP_KEY).
            'eracuni_username' => 'encrypted',
            'eracuni_secret_key' => 'encrypted',
            'eracuni_token' => 'encrypted',
            'eracuni_endpoint' => 'encrypted',
        ];
    }
}
