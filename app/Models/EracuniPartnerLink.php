<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Links a payer (parent, identified by e-mail) to a partner in eRačuni. Created only
 * after an admin confirms a match or a "create new partner" job succeeds — never automatically.
 */
final class EracuniPartnerLink extends Model
{
    protected $fillable = [
        'payer_email',
        'payer_name',
        'oib',
        'eracuni_partner_id',
        'eracuni_partner_name',
        'linked_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'immutable_datetime',
        ];
    }
}
