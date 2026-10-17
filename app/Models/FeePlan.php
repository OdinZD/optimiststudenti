<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeePlanType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A membership tariff (Članarine): a named amount with a type (monthly / yearly /
 * one-time). Retired tariffs are deactivated, not deleted, while assignments exist.
 */
final class FeePlan extends Model
{
    protected $fillable = [
        'name',
        'amount_cents',
        'type',
        'active',
    ];

    /** @return HasMany<StudentFeePlan, $this> */
    public function studentFeePlans(): HasMany
    {
        return $this->hasMany(StudentFeePlan::class);
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'type' => FeePlanType::class,
            'active' => 'boolean',
        ];
    }
}
